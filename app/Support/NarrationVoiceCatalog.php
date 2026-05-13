<?php

namespace App\Support;

final class NarrationVoiceCatalog
{
    /**
     * Human label for a configured ElevenLabs voice id (from config/poi_narration.php voices).
     */
    public static function labelForVoiceId(?string $voiceId): ?string
    {
        if ($voiceId === null || $voiceId === '') {
            return null;
        }

        $voices = config('poi_narration.voices', []);
        if (! is_array($voices)) {
            return null;
        }

        foreach ($voices as $def) {
            if (! is_array($def)) {
                continue;
            }
            if (($def['id'] ?? '') !== $voiceId) {
                continue;
            }
            $label = trim((string) ($def['label'] ?? ''));

            return $label !== '' ? $label : null;
        }

        return null;
    }

    /**
     * Prefer stored label from generation time; fall back to current config; then a generic string.
     */
    public static function displayLabel(?string $storedLabel, ?string $voiceId): string
    {
        $stored = $storedLabel !== null ? trim($storedLabel) : '';
        if ($stored !== '') {
            return $stored;
        }

        return self::labelForVoiceId($voiceId) ?? 'Unknown voice';
    }
}
