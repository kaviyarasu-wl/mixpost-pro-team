<?php

namespace Inovector\Mixpost\Http\Base\Controllers\Workspace\AI;

use App\Helpers\UsageTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Http\Base\Requests\Workspace\AI\AIGenerateText;

class AIGenerateTextController extends Controller
{
    public function __invoke(AIGenerateText $generateText): JsonResponse
    {
        $response = $generateText->handle();

        // Track usage
        app(UsageTracker::class)->track(request()->input('text') ?? '', $response, getModelName());

        return response()->json([
            'text' => $response
        ]);
    }
}
