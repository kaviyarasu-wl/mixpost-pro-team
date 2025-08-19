<?php

namespace Inovector\Mixpost\Http\Api\Controllers\Workspace\Post;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Actions\Post\RedirectAfterDeletedPost;
use Inovector\Mixpost\Events\Post\PostDeleted;
use Inovector\Mixpost\Models\Post;

class DeletePostsController extends Controller
{
    public function __invoke(Request $request, RedirectAfterDeletedPost $redirectAfterPostDeleted): JsonResponse
    {
        $query = Post::whereIn('uuid', $request->input('posts'));

        if (!$request->get('trash')) {
            $deleted = $query->forceDelete();

            PostDeleted::dispatch($request->input('posts'), false);

            return response()->json([
                'deleted' => $deleted,
            ]);
        }

        $trashed = $query->delete();

        PostDeleted::dispatch($request->input('posts'), true);

        return response()->json([
            'to_trash' => $trashed,
        ]);
    }
}
