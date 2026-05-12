<?php

namespace App\Support;

use Illuminate\Support\Str;

final class AustraliaGeography
{
    /**
     * @return list<string>
     */
    public static function states(): array
    {
        return config('australia_geography.states', []);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function regionsByState(): array
    {
        return config('australia_geography.regions', []);
    }

    /**
     * @return list<string>
     */
    public static function regionsForState(?string $state): array
    {
        if ($state === null || $state === '') {
            return [];
        }

        $canonical = self::normalizeStateInput($state);

        return self::regionsByState()[$canonical] ?? [];
    }

    /**
     * Map abbreviations or mixed case to canonical full state name.
     */
    public static function normalizeStateInput(string $input): string
    {
        $t = trim($input);
        if ($t === '') {
            return $t;
        }

        $legacy = config('australia_geography.legacy_state_codes', []);
        $upper = strtoupper($t);
        if (isset($legacy[$upper])) {
            return $legacy[$upper];
        }

        foreach (self::states() as $name) {
            if (strcasecmp($name, $t) === 0) {
                return $name;
            }
        }

        return $t;
    }

    /**
     * Best-effort match of a free-text region (e.g. from spreadsheets) to a canonical option.
     */
    public static function canonicalizeRegion(string $stateFullName, ?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $rawTrim = trim($raw);
        if ($rawTrim === '') {
            return null;
        }

        $options = self::regionsForState($stateFullName);
        foreach ($options as $opt) {
            if (strcasecmp($opt, $rawTrim) === 0) {
                return $opt;
            }
        }

        foreach ($options as $opt) {
            if (Str::contains(Str::lower($opt), Str::lower($rawTrim)) || Str::contains(Str::lower($rawTrim), Str::lower($opt))) {
                return $opt;
            }
        }

        return $rawTrim;
    }
}
