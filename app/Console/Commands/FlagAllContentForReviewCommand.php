<?php

namespace App\Console\Commands;

use App\Enums\PoiVerificationStatus;
use App\Models\Poi;
use App\Models\Town;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlagAllContentForReviewCommand extends Command
{
    protected $signature = 'content:flag-all-for-review
                            {--force : Skip confirmation prompt}
                            {--towns-status=pending : Town publication status: pending (review queue) or draft}
                            {--pois-status=pending : POI publication status: pending (review queue) or draft}';

    protected $description = 'Set every town to pending or draft (unpublished) and unverified, and every POI to pending or draft with not verified';

    public function handle(): int
    {
        $townsStatus = (string) $this->option('towns-status');
        if (! in_array($townsStatus, ['pending', 'draft'], true)) {
            $this->error('Invalid --towns-status. Use pending or draft.');

            return self::FAILURE;
        }

        $poisStatus = (string) $this->option('pois-status');
        if (! in_array($poisStatus, ['pending', 'draft'], true)) {
            $this->error('Invalid --pois-status. Use pending or draft.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $townLabel = $townsStatus === 'pending' ? 'pending (review queue)' : 'draft';
            $poiLabel = $poisStatus === 'pending' ? 'pending (review queue)' : 'draft';
            if (! $this->confirm(
                "This will set ALL towns to {$townLabel} (unpublished) and unverified, and ALL POIs to {$poiLabel} with Not verified. Continue?",
                false
            )) {
                $this->line('Aborted.');

                return self::SUCCESS;
            }
        }

        $townCount = Town::query()->count();
        $poiCount = Poi::query()->count();

        DB::transaction(function () use ($townsStatus, $poisStatus): void {
            Town::query()->update([
                'status' => $townsStatus,
                'published_at' => null,
                'published_by' => null,
                'verification_status' => 'unverified',
                'verified_at' => null,
                'verified_by' => null,
            ]);

            Poi::query()->update([
                'status' => $poisStatus,
                'published_at' => null,
                'published_by' => null,
                'verification_status' => PoiVerificationStatus::NotVerified,
            ]);
        });

        $this->info("Updated {$townCount} towns and {$poiCount} POIs.");

        return self::SUCCESS;
    }
}
