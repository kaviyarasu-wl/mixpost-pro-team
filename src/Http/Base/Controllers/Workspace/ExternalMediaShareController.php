<?php

namespace Inovector\Mixpost\Http\Base\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Http\Base\Requests\Workspace\MediaDownloadGravityWrite;
use Inovector\Mixpost\Models\User;

class ExternalMediaShareController extends Controller
{
    public function __invoke(MediaDownloadGravityWrite $request): Response|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('mixpost.login')->with('external_media_import', $request->all());
        }

        $workspace = User::find(auth()->id())->workspaces()->first();

        $response = $request->handle();

        // If it's JSON, you might want to handle it differently
        if (! $response->isRedirect()) {
            $data = json_decode($response->getContent(), true);
            
            return redirect()->route('mixpost.media.index', ['workspace' => $workspace->uuid])
                ->with('downloaded_media', $data);
        }

        return $response;
    }
}
