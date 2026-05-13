<?php

namespace App\Services;

use App\Enums\PoiVerificationStatus;
use App\Models\Poi;
use App\Models\Town;
use App\Support\AustraliaGeography;
use App\Support\Xlsx\PoiStarterWorkbookReader;

final class PoiSpreadsheetImportService
{
    public function __construct(
        private readonly PoiStarterWorkbookReader $reader,
        private readonly TownAboutHtmlSanitizer $htmlSanitizer,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int, missingTown: int}
     */
    public function importFile(string $absolutePath, string $state): array
    {
        $stateFull = AustraliaGeography::normalizeStateInput($state);
        $rows = $this->reader->readDataRows($absolutePath);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $missingTown = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['poi_name'] ?? ''));
            if ($name === '') {
                $skipped++;

                continue;
            }

            $nearestTown = trim((string) ($row['nearest_town'] ?? ''));
            if ($nearestTown === '') {
                $skipped++;

                continue;
            }

            $town = Town::query()->where('name', $nearestTown)->where('state', $stateFull)->first();
            if ($town === null) {
                $missingTown++;

                continue;
            }

            $payload = $this->mapRowToPoiAttributes($row, $stateFull, $town->id);
            $poi = Poi::query()
                ->where('name', $name)
                ->where('town_id', $town->id)
                ->where('state', $stateFull)
                ->first();

            if ($poi) {
                $poi->update($payload);
                $updated++;
            } else {
                Poi::create($payload);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped', 'missingTown');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRowToPoiAttributes(array $row, string $stateFull, int $townId): array
    {
        $lat = $this->floatOrNull($row['latitude'] ?? null);
        $lng = $this->floatOrNull($row['longitude'] ?? null);

        $hook = trim((string) ($row['editorial_hook'] ?? ''));
        $short = $hook !== '' ? mb_substr($hook, 0, 180) : null;

        $aboutHtml = null;
        if ($hook !== '') {
            $escaped = htmlspecialchars($hook, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $aboutHtml = $this->htmlSanitizer->sanitize('<p>'.$escaped.'</p>');
            if ($aboutHtml === '') {
                $aboutHtml = null;
            }
        }

        return [
            'country' => config('australia_geography.country_default', 'AU'),
            'name' => trim((string) ($row['poi_name'] ?? '')),
            'categories' => $this->resolveCategories($row),
            'town_id' => $townId,
            'state' => $stateFull,
            'status' => 'published',
            'verification_status' => PoiVerificationStatus::NotVerified->value,
            'verified_at' => null,
            'short_description' => $short,
            'latitude' => $lat,
            'longitude' => $lng,
            'about_html' => $aboutHtml,
            'spreadsheet_notes' => $this->nullableString($row['notes'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function resolveCategories(array $row): array
    {
        $taxonomy = config('poi_taxonomy.categories', []);
        $out = [];

        $concept = trim((string) ($row['likely_poi_categories_concept'] ?? ''));
        if ($concept !== '') {
            foreach (preg_split('/[,;&]+/', $concept) ?: [] as $part) {
                $t = trim((string) $part);
                if ($t === '') {
                    continue;
                }
                foreach ($taxonomy as $allowed) {
                    if (strcasecmp($t, $allowed) === 0) {
                        $out[] = $allowed;

                        break;
                    }
                }
            }
        }

        $sheetCat = trim((string) ($row['category'] ?? ''));
        if ($out === [] && $sheetCat !== '') {
            foreach ($taxonomy as $allowed) {
                if (strcasecmp($sheetCat, $allowed) === 0) {
                    $out[] = $allowed;

                    break;
                }
            }
        }

        if ($out === []) {
            return ['Deep Roots'];
        }

        return array_values(array_unique($out));
    }

    private function nullableString(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    private function floatOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
        $s = trim((string) $v);
        if ($s === '' || ! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }
}
