<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Town;
use App\Services\TownAboutAiDraftService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TownAboutAiDraftController extends Controller
{
    public function __invoke(Town $town, TownAboutAiDraftService $ai): JsonResponse
    {
        if (! $ai->isConfigured()) {
            return response()->json([
                'message' => 'Add OPENAI_API_KEY and/or ANTHROPIC_API_KEY to .env to enable AI drafting.',
            ], 503);
        }

        try {
            $html = $ai->draftHtml($town->fresh());
        } catch (RuntimeException $e) {
            report($e);

            return response()->json([
                'message' => 'The AI service returned an error. Check logs or try again in a moment.',
            ], 502);
        }

        return response()->json(['html' => $html]);
    }
}
