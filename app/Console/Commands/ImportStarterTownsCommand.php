<?php

namespace App\Console\Commands;

use App\Services\TownSpreadsheetImportService;
use Illuminate\Console\Command;

class ImportStarterTownsCommand extends Command
{
    protected $signature = 'towns:import-starter
                            {--vic= : Path to VIC_Towns_Starter_v1.xlsx}
                            {--nsw= : Path to NSW_Towns_Starter_v1.xlsx}
                            {--dry-run : Parse files and report row counts only}';

    protected $description = 'Import NSW/VIC starter town spreadsheets into the towns table';

    public function handle(TownSpreadsheetImportService $importer): int
    {
        $defaultVic = 'C:\\Apache24\\htdocs\\unbeaten\\Data\\VIC_Towns_Starter_v1.xlsx';
        $defaultNsw = 'C:\\Apache24\\htdocs\\unbeaten\\Data\\NSW_Towns_Starter_v1.xlsx';

        $pairs = [
            ['path' => $this->option('vic') ?: $defaultVic, 'state' => 'Victoria'],
            ['path' => $this->option('nsw') ?: $defaultNsw, 'state' => 'New South Wales'],
        ];

        if ($this->option('dry-run')) {
            $reader = new \App\Support\Xlsx\TownStarterWorkbookReader;
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
            $this->line("  created: {$r['created']}, updated: {$r['updated']}, skipped empty rows: {$r['skipped']}");
        }

        $this->info("Done. Total created: {$totalCreated}, updated: {$totalUpdated}, skipped: {$totalSkipped}");

        return self::SUCCESS;
    }
}
