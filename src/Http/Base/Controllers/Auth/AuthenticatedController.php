<?php

namespace Inovector\Mixpost\Http\Base\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Actions\Auth\AttemptToAuthenticate;
use Inovector\Mixpost\Actions\Auth\EnsureLoginIsNotThrottled;
use Inovector\Mixpost\Actions\Auth\PrepareAuthenticatedSession;
use Inovector\Mixpost\Actions\Auth\RedirectIfTwoFactorAuthenticatable;
use Inovector\Mixpost\Concerns\UsesAuth;
use Inovector\Mixpost\Concerns\UsesUserModel;
use Inovector\Mixpost\Enums\WorkspaceUserRole;
use Inovector\Mixpost\Facades\Settings as SettingsFacade;
use Inovector\Mixpost\Features;
use Inovector\Mixpost\Http\Base\Requests\Auth\LoginRequest;
use Inovector\Mixpost\Models\Setting;
use Inovector\Mixpost\Models\Workspace;
use Inovector\Mixpost\Util;
use Laravel\Socialite\Facades\Socialite;
use Stevebauman\Location\Facades\Location;

class AuthenticatedController extends Controller
{
    use UsesAuth;
    use UsesUserModel;

    public function create(): Response|RedirectResponse
    {
        if (!self::getUserClass()::exists()) {
            return redirect()->route('mixpost.installation');
        }

        return Socialite::driver('gravity')->redirect();

        // return Inertia::render('Auth/Login', [
        //     'locales' => Util::config('locales'),
        //     'is_forgot_password_enabled' => Features::isForgotPasswordEnabled()
        // ]);
    }

    public function redirect($driver)
    {
        return Socialite::driver($driver)->redirect();
    }

    public function callback($driver)
    {
        try {
            $response = Socialite::driver($driver)->user();
        } catch (\Exception $e) {
            return redirect()->route('mixpost.login')->withErrors(['error' => 'Something went wrong! Please try again.']);
        }

        $user = User::where('email', $response->getEmail())->first();

        if (!$user) {
            throw new \Exception('User not found');
        }
        Auth::login($user, true);

        postHogIdentify($user);

        $setting = Setting::where('user_id', $user->id)->where('name', 'timezone')->first();
        if (!$setting) {
            $timezone = Location::get(request()->ip())?->timezone ?? "UTC";
            Setting::updateOrCreate(['name' => 'timezone', 'user_id' => $user->id], ['payload' => $timezone]);
            $setting = Setting::where('user_id', $user->id)->where('name', 'timezone')->first();
        }
        SettingsFacade::put('timezone', $setting->payload ?? 'UTC');

        if (DB::table('mixpost_workspace_user')->where('user_id', $user->id)->count() === 0) {
            $workspace = Workspace::create([
                'name' => "$user->name Workspace",
                'hex_color' => '2E41FF'
            ]);

            $workspace->attachUser(
                id: Auth::id(),
                role: WorkspaceUserRole::ADMIN,
                canApprove: true
            );
        }

        $cacheKey = 'external_media_import:' . request()->ip();
        $externalImportData = Cache::get($cacheKey);
        if ($externalImportData) {
            Cache::forget($cacheKey);

            return to_route('mixpost.share-media', ['files' => $externalImportData]);
        }

        return redirect()->route('mixpost.home');
    }

    public function store(LoginRequest $request)
    {
        return (new Pipeline(app()))->send($request)->through(array_filter([
            EnsureLoginIsNotThrottled::class,
            Features::isTwoFactorAuthEnabled() ? RedirectIfTwoFactorAuthenticatable::class : null,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]))->then(function () {
            return redirect()->intended(route('mixpost.home'));
        });
    }

    /**
     * Log out of the session and redirect to the gravity global logout page,
     * which will then redirect back to the logout page of this application.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function globalLogout(Request $request): RedirectResponse
    {
        $url = config('services.gravity.host') . '/global/logout?' . http_build_query([
            'client_id' => config('services.gravity.client_id'),
            'redirect_uri' => route('mixpost.logout'),
            'access_token' => session()->get('gravity_access_token')
        ]);

        self::getAuthGuard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->away($url);
    }

    public function destroy(Request $request): RedirectResponse
    {
        self::getAuthGuard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('mixpost.login');
    }
}
