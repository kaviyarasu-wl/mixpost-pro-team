<?php

namespace Inovector\Mixpost\Http\Api\Controllers\Workspace\Post;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Builders\Post\PostQuery;
use Inovector\Mixpost\Events\Post\PostDeleted;
use Inovector\Mixpost\Http\Api\Requests\Workspace\Post\StorePost;
use Inovector\Mixpost\Http\Api\Requests\Workspace\Post\UpdatePost;
use Inovector\Mixpost\Http\Api\Resources\PostResource;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Support\EagerLoadPostVersionsMedia;

class PostsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = PostQuery::apply($request)
            ->latest()
            ->latest('id')
            ->paginate(20);

        EagerLoadPostVersionsMedia::apply($posts);

        return PostResource::collection($posts);
    }

    public function store(StorePost $storePost): PostResource
    {
        $record = $storePost->handle();

        $record->refresh();

        $record->load('accounts', 'versions', 'user', 'tags');

        EagerLoadPostVersionsMedia::apply($record);

        return new PostResource($record);
    }

    public function show(Request $request): PostResource
    {
        $record = Post::firstOrFailByUuid($request->route('post'));

        $record->load('accounts', 'versions', 'user', 'tags');

        EagerLoadPostVersionsMedia::apply($record);

        return new PostResource($record);
    }

    public function update(UpdatePost $updatePost): JsonResponse
    {
        return response()->json([
            'success' => (bool)$updatePost->handle(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $query = Post::where('uuid', $request->route('post'));

        if (!$request->get('trash')) {
            $deleted = $query->forceDelete();

            PostDeleted::dispatch([$request->route('post')], false);

            return response()->json([
                'deleted' => $deleted,
            ]);
        }

        $deleted = $query->delete();

        PostDeleted::dispatch([$request->route('post')], false);

        return response()->json([
            'to_trash' => $deleted,
        ]);
    }
}
