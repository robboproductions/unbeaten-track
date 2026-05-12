<?php

namespace App\Services;

use App\Models\Town;
use App\Support\AustraliaGeography;
use App\Support\Xlsx\TownStarterWorkbookReader;

final class TownSpreadsheetImportService
{
    public function __construct(
        private readonly TownStarterWorkbookReader $reader = new TownStarterWorkbookReader,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function importFile(string $absolutePath, string $state): array
    {
        $stateFull = AustraliaGeography::normalizeStateInput($state);
        $rows = $this->reader->readDataRows($absolutePath);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['town'] ?? ''));
            if ($name === '') {
                $skipped++;

                continue;
            }

            $payload = $this->mapRowToTownAttributes($row, $stateFull);

            $town = Town::query()->where('name', $name)->where('state', $stateFull)->first();
            if ($town) {
                $town->update($payload);
                $updated++;
            } else {
                Town::create($payload);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRowToTownAttributes(array $row, string $stateFull): array
    {
        $rawRegion = $this->nullableString($row['region'] ?? null);
        $region = $rawRegion !== null
            ? (AustraliaGeography::canonicalizeRegion($stateFull, $rawRegion) ?? $rawRegion)
            : null;

        return [
            'country' => config('australia_geography.country_default', 'AU'),
            'name' => trim((string) $row['town']),
            'state' => $stateFull,
            'region' => $region,
            'status' => 'published',
            'population_approx' => $this->intOrNull($row['approx_pop'] ?? null),
            'latitude' => $this->floatOrNull($row['latitude'] ?? null),
            'longitude' => $this->floatOrNull($row['longitude'] ?? null),
            'has_pub' => $this->yn($row['pub'] ?? null),
            'has_cafe' => $this->yn($row['cafe'] ?? null),
            'has_shop' => $this->yn($row['shop'] ?? null),
            'has_fuel' => $this->yn($row['fuel'] ?? null),
            'has_caravan_park' => $this->yn($row['caravan_park'] ?? null),
            'editorial_hook' => $this->nullableString($row['editorial_hook'] ?? null),
            'likely_poi_categories' => $this->truncateString($row['likely_poi_categories'] ?? null, 255),
            'suggested_corridor' => $this->truncateString($row['suggested_corridor'] ?? null, 255),
            'spreadsheet_notes' => $this->nullableString($row['notes'] ?? null),
        ];
    }

    private function nullableString(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    private function truncateString(mixed $v, int $max): ?string
    {
        $s = $this->nullableString($v);
        if ($s === null) {
            return null;
        }

        return mb_strlen($s) > $max ? mb_substr($s, 0, $max) : $s;
    }

    private function intOrNull(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_int($v)) {
            return $v;
        }
        if (is_float($v)) {
            return (int) round($v);
        }
        if (is_numeric($v)) {
            return (int) round((float) $v);
        }

        return null;
    }

    private function floatOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_float($v) || is_int($v)) {
            return (float) $v;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        return null;
    }

    private function yn(mixed $v): bool
    {
        if ($v === true) {
            return true;
        }
        $s = strtoupper(trim((string) $v));

        return in_array($s, ['Y', 'YES', '1', 'TRUE'], true);
    }
}
