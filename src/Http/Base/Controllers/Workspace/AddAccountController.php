<?php

namespace Inovector\Mixpost\Http\Base\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inovector\Mixpost\Events\Account\AddingAccount;
use Inovector\Mixpost\Facades\SocialProviderManager;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AddAccountController extends Controller
{
    public function __invoke(HttpRequest $request): Response|RedirectResponse
    {
        $providerName = $request->route('provider');

        $accountLimit = User::find(auth()->id())->userSubscription?->plan?->account_limit ?? 0;
        if (Account::getCurrentUserAccounts()->count() >= $accountLimit) {
            return back()->with('error', 'You have reached the maximum number of accounts.');
        }

        $provider = SocialProviderManager::connect($providerName, ['state' => WorkspaceManager::current()->uuid]);

        AddingAccount::dispatch($provider);

        $url = $provider->getAuthUrl();

        if (Request::inertia()) {
            return Inertia::location($url);
        }

        return redirect()->away($url);
    }
}
