<?php

namespace Inovector\Mixpost\Http\Base\Controllers\Workspace\Post;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Actions\Post\RedirectAfterDeletedPost;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Events\Post\PostDeleted;
use Inovector\Mixpost\Models\Post;

class DeletePostsController extends Controller
{
    public function __invoke(Request $request, RedirectAfterDeletedPost $redirectAfterPostDeleted): RedirectResponse
    {
        $query = Post::whereIn('uuid', $request->input('posts'));

        $scheduledPosts = Post::whereIn('uuid', $request->input('posts'))->where('status', PostStatus::SCHEDULED->value)->get();
        foreach ($scheduledPosts as $scheduledPost) {
            $scheduledPost->unSetUsageLimit();
        }

        if ($request->get('status') === 'trash') {
            $query->forceDelete();

            PostDeleted::dispatch($request->input('posts'), false);
        }

        if ($request->get('status') !== 'trash') {
            $query->delete();

            PostDeleted::dispatch($request->input('posts'), true);
        }

        return $redirectAfterPostDeleted($request);
    }
}
