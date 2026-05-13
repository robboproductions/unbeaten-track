<?php

namespace App\Services;

use App\Models\Poi;
use App\Models\User;
use App\Services\Narration\Contracts\NarrationProvider;
use App\Services\Narration\NarrationGenerationException;
use App\Support\NarrationVoiceCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PoiNarrationService
{
    public function __construct(
        private readonly NarrationProvider $provider,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('poi_narration.elevenlabs.api_key'));
    }

    public function isStale(Poi $poi): bool
    {
        if (! $poi->has_narration) {
            return false;
        }

        return $poi->narration_script_hash !== $this->currentHash($poi);
    }

    public function currentHash(Poi $poi): string
    {
        $voiceId = $this->resolveVoiceId($poi);
        $modelId = $this->resolveModelId($poi);
        $script = (string) ($poi->narration_script ?? '');

        return hash('sha256', $script.'|'.$voiceId.'|'.$modelId);
    }

    public function generate(Poi $poi, User $generatedBy): Poi
    {
        if (! config('poi_narration.enabled')) {
            throw new NarrationGenerationException('POI narration is disabled.');
        }

        $script = trim((string) ($poi->narration_script ?? ''));
        if ($script === '') {
            throw new NarrationGenerationException('Add a narration script before generating audio.');
        }

        $maxChars = (int) config('poi_narration.limits.max_script_characters', 5000);
        if (mb_strlen($script) > $maxChars) {
            throw new NarrationGenerationException('Narration script exceeds the maximum length ('.$maxChars.' characters).');
        }

        $voiceId = $this->resolveVoiceId($poi);
        $modelId = $this->resolveModelId($poi);

        $result = $this->provider->synthesize($script, $voiceId, $modelId);

        $hash = hash('sha256', $script.'|'.$voiceId.'|'.$modelId);
        $dir = (string) config('poi_narration.storage.directory', 'poi-narrations');
        $filename = $dir.'/'.$poi->id.'-'.mb_substr($hash, 0, 12).'.mp3';

        $disk = Storage::disk((string) config('poi_narration.storage.disk', 'public'));
        $previousPath = $poi->narration_audio_path;

        try {
            DB::transaction(function () use ($poi, $filename, $voiceId, $modelId, $hash, $result, $generatedBy, $disk, $previousPath): void {
                $disk->put($filename, $result->audio);

                $voiceLabel = NarrationVoiceCatalog::labelForVoiceId($voiceId);

                $poi->forceFill([
                    'narration_voice_id' => $voiceId,
                    'narration_voice_label' => $voiceLabel,
                    'narration_model_id' => $modelId,
                    'narration_audio_path' => $filename,
                    'narration_audio_duration_seconds' => $result->durationSeconds,
                    'narration_audio_bytes' => $result->bytes,
                    'narration_script_hash' => $hash,
                    'narration_generated_at' => now(),
                    'narration_generated_by' => $generatedBy->id,
                ])->save();

                if ($previousPath !== null && $previousPath !== '' && $previousPath !== $filename && $disk->exists($previousPath)) {
                    $disk->delete($previousPath);
                }
            });
        } catch (\Throwable $e) {
            if ($disk->exists($filename)) {
                $disk->delete($filename);
            }
            throw $e;
        }

        return $poi->fresh();
    }

    public function deleteAudio(Poi $poi): void
    {
        $path = $poi->narration_audio_path;
        if (filled($path)) {
            $disk = Storage::disk((string) config('poi_narration.storage.disk', 'public'));
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $poi->forceFill([
            'narration_voice_id' => null,
            'narration_voice_label' => null,
            'narration_model_id' => null,
            'narration_audio_path' => null,
            'narration_audio_duration_seconds' => null,
            'narration_audio_bytes' => null,
            'narration_script_hash' => null,
            'narration_generated_at' => null,
            'narration_generated_by' => null,
        ])->save();
    }

    private function resolveVoiceId(Poi $poi): string
    {
        $id = trim((string) ($poi->narration_voice_id ?? ''));
        if ($id !== '') {
            return $id;
        }
        $default = trim((string) config('poi_narration.elevenlabs.default_voice_id', ''));
        if ($default === '') {
            throw new RuntimeException('No ElevenLabs voice ID is configured.');
        }

        return $default;
    }

    private function resolveModelId(Poi $poi): string
    {
        $id = trim((string) ($poi->narration_model_id ?? ''));
        if ($id !== '') {
            return $id;
        }

        return (string) config('poi_narration.elevenlabs.default_model_id', 'eleven_multilingual_v2');
    }
}
