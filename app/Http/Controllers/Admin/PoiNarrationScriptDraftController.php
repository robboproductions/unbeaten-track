<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poi;
use App\Services\PoiAboutAiDraftService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PoiNarrationScriptDraftController extends Controller
{
    public function __invoke(Poi $poi, PoiAboutAiDraftService $ai): JsonResponse
    {
        if (! $ai->isConfigured()) {
            return response()->json([
                'message' => 'Add ANTHROPIC_API_KEY (Claude) or OPENAI_API_KEY to .env to enable drafting.',
            ], 503);
        }

        try {
            $script = $ai->draftNarrationScript($poi->fresh());
        } catch (RuntimeException $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json(['script' => $script]);
    }
}
