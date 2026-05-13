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

    /**
     * Portrait for known narrators (files under public/images/narrators/{slug}.png).
     */
    public static function narratorPortraitUrl(?string $voiceId, ?string $storedLabel): ?string
    {
        $slug = self::narratorPortraitSlug($voiceId, $storedLabel);

        return $slug !== null ? asset('images/narrators/'.$slug.'.png') : null;
    }

    /**
     * @return non-empty-string|null terry|sarah
     */
    public static function narratorPortraitSlug(?string $voiceId, ?string $storedLabel): ?string
    {
        $voices = config('poi_narration.voices', []);
        if (is_array($voices) && $voiceId !== null && $voiceId !== '') {
            foreach ($voices as $slug => $def) {
                if (! is_array($def)) {
                    continue;
                }
                if (($def['id'] ?? '') !== $voiceId) {
                    continue;
                }
                $slug = is_string($slug) ? $slug : '';
                if ($slug === 'terry' || $slug === 'sarah') {
                    return $slug;
                }
            }
        }

        $norm = strtolower(trim(self::displayLabel($storedLabel, $voiceId)));

        return match ($norm) {
            'terry', 'baxter' => 'terry',
            'sarah', 'zoe' => 'sarah',
            default => null,
        };
    }

    /**
     * Short intro clip for the narrator (public/audio/narrator-intros/{slug}_intro.mp3).
     */
    public static function narratorIntroUrl(?string $voiceId, ?string $storedLabel): ?string
    {
        $slug = self::narratorPortraitSlug($voiceId, $storedLabel);

        return $slug !== null ? asset('audio/narrator-intros/'.$slug.'_intro.mp3') : null;
    }

    /**
     * Voices from config for the admin “who are our narrators” panel.
     * Order: Sarah first, Terry second, then any other configured voices.
     *
     * @return list<array{slug: string, label: string, voice_id: string, portrait_url: ?string, intro_url: ?string}>
     */
    public static function narratorsForShowcase(): array
    {
        $voices = config('poi_narration.voices', []);
        if (! is_array($voices)) {
            return [];
        }

        $bySlug = [];
        foreach ($voices as $slug => $def) {
            if (! is_string($slug) || $slug === '' || ! is_array($def)) {
                continue;
            }
            $voiceId = trim((string) ($def['id'] ?? ''));
            if ($voiceId === '') {
                continue;
            }
            $label = trim((string) ($def['label'] ?? ''));
            if ($label === '') {
                $label = ucfirst($slug);
            }
            $bySlug[$slug] = [
                'slug' => $slug,
                'label' => $label,
                'voice_id' => $voiceId,
                'portrait_url' => self::narratorPortraitUrl($voiceId, $label),
                'intro_url' => self::narratorIntroUrl($voiceId, $label),
            ];
        }

        $out = [];
        foreach (['sarah', 'terry'] as $preferred) {
            if (isset($bySlug[$preferred])) {
                $out[] = $bySlug[$preferred];
                unset($bySlug[$preferred]);
            }
        }
        foreach ($bySlug as $row) {
            $out[] = $row;
        }

        return $out;
    }
}
