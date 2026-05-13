<?php

namespace App\Console\Commands;

use App\Services\PoiSpreadsheetImportService;
use App\Support\Xlsx\PoiStarterWorkbookReader;
use Illuminate\Console\Command;

class ImportStarterPoisCommand extends Command
{
    protected $signature = 'pois:import-starter
                            {--vic= : Path to VIC_POIs_Starter_v1.xlsx}
                            {--nsw= : Path to NSW_POIs_Starter_v1.xlsx}
                            {--dry-run : Parse files and report row counts only}';

    protected $description = 'Import NSW/VIC starter POI spreadsheets into the pois table';

    public function handle(PoiSpreadsheetImportService $importer): int
    {
        $defaultVic = 'C:\\Apache24\\htdocs\\unbeaten\\Data\\VIC_POIs_Starter_v1.xlsx';
        $defaultNsw = 'C:\\Apache24\\htdocs\\unbeaten\\Data\\NSW_POIs_Starter_v1.xlsx';

        $pairs = [
            ['path' => $this->option('vic') ?: $defaultVic, 'state' => 'Victoria'],
            ['path' => $this->option('nsw') ?: $defaultNsw, 'state' => 'New South Wales'],
        ];

        if ($this->option('dry-run')) {
            $reader = new PoiStarterWorkbookReader;
            foreach ($pairs as $pair) {
                if (! is_readable($pair['path'])) {
                    $this->warn("Skip (not readable): {$pair['path']}");

                    continue;
                }
                $rows = $reader->readDataRows($pair['path']);
                $this->info("{$pair['state']}: {$pair['path']} — ".count($rows).' data rows');
            }

            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalMissingTown = 0;

        foreach ($pairs as $pair) {
            if (! is_readable($pair['path'])) {
                $this->warn("Skip (not readable): {$pair['path']}");

                continue;
            }

            $this->info("Importing {$pair['state']} from {$pair['path']} …");
            $r = $importer->importFile($pair['path'], $pair['state']);
            $totalCreated += $r['created'];
            $totalUpdated += $r['updated'];
            $totalSkipped += $r['skipped'];
            $totalMissingTown += $r['missingTown'];
            $this->line("  created: {$r['created']}, updated: {$r['updated']}, skipped empty rows: {$r['skipped']}, missing town: {$r['missingTown']}");
        }

        $this->info("Done. Total created: {$totalCreated}, updated: {$totalUpdated}, skipped: {$totalSkipped}, missing town: {$totalMissingTown}");

        return self::SUCCESS;
    }
}
