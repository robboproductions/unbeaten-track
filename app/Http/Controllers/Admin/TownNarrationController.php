<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Town;
use App\Services\Narration\NarrationGenerationException;
use App\Services\TownNarrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class TownNarrationController extends Controller
{
    public function generate(Request $request, Town $town, TownNarrationService $narration): RedirectResponse
    {
        if (! $narration->isConfigured()) {
            return back()->withErrors([
                'narration' => 'Set ELEVENLABS_API_KEY in .env to generate narration (the key stays on the server only).',
            ]);
        }

        $allowedVoiceIds = collect(config('poi_narration.voices', []))
            ->pluck('id')
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($allowedVoiceIds === []) {
            return back()->withErrors([
                'narration' => 'No narration voices are configured in config/poi_narration.php.',
            ]);
        }

        $request->validate([
            'narration_voice_id' => ['required', 'string', 'max:64', Rule::in($allowedVoiceIds)],
        ]);

        $town->forceFill([
            'narration_voice_id' => $request->string('narration_voice_id')->toString(),
        ])->save();

        $key = 'town-narration:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors([
                'narration' => 'Too many narration requests. Try again in about a minute.',
            ]);
        }

        RateLimiter::hit($key, 60);

        try {
            $narration->generate($town->fresh(), $request->user());
        } catch (NarrationGenerationException $e) {
            return back()->withErrors(['narration' => $e->getMessage()]);
        }

        return back()->with('status', 'Narration audio generated.');
    }

    public function destroy(Town $town, TownNarrationService $narration): RedirectResponse
    {
        $narration->deleteAudio($town->fresh());

        return back()->with('status', 'Narration audio removed.');
    }
}
