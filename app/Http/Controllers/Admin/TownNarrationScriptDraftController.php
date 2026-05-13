<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Town;
use App\Services\TownAboutAiDraftService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TownNarrationScriptDraftController extends Controller
{
    public function __invoke(Town $town, TownAboutAiDraftService $ai): JsonResponse
    {
        if (! $ai->isConfigured()) {
            return response()->json([
                'message' => 'Add ANTHROPIC_API_KEY (Claude) or OPENAI_API_KEY to .env to enable drafting.',
            ], 503);
        }

        try {
            $script = $ai->draftNarrationScript($town->fresh());
        } catch (RuntimeException $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json(['script' => $script]);
    }
}
