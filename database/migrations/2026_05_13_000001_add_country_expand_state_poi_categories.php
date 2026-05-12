<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->char('country', 2)->default('AU')->after('id');
        });

        Schema::table('pois', function (Blueprint $table) {
            $table->char('country', 2)->default('AU')->after('id');
            $table->json('categories')->nullable()->after('name');
        });

        $this->expandStateColumns();
        $this->normalizeStateAbbreviations();
        $this->backfillPoiCategoriesAndDropCategory();
    }

    public function down(): void
    {
        Schema::table('pois', function (Blueprint $table) {
            $table->string('category', 32)->nullable()->after('name');
        });

        foreach (DB::table('pois')->orderBy('id')->cursor() as $row) {
            $cats = json_decode($row->categories ?? '[]', true);
            $first = is_array($cats) && $cats !== [] ? (string) $cats[0] : 'Deep Roots';
            DB::table('pois')->where('id', $row->id)->update(['category' => mb_substr($first, 0, 32)]);
        }

        Schema::table('pois', function (Blueprint $table) {
            $table->dropColumn(['categories', 'country']);
        });

        Schema::table('towns', function (Blueprint $table) {
            $table->dropColumn('country');
        });

        $this->revertStateNamesToAbbrev();
        $this->narrowStateColumns();
    }

    private function expandStateColumns(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->string('state', 64)->change();
        });

        Schema::table('pois', function (Blueprint $table) {
            $table->string('state', 64)->change();
        });
    }

    private function narrowStateColumns(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->string('state', 8)->change();
        });

        Schema::table('pois', function (Blueprint $table) {
            $table->string('state', 8)->change();
        });
    }

    private function normalizeStateAbbreviations(): void
    {
        $map = config('australia_geography.legacy_state_codes', []);

        foreach (['towns', 'pois'] as $table) {
            foreach ($map as $abbr => $full) {
                DB::table($table)->where('state', $abbr)->update(['state' => $full]);
            }
        }
    }

    private function revertStateNamesToAbbrev(): void
    {
        $map = array_flip(config('australia_geography.legacy_state_codes', []));

        foreach (['towns', 'pois'] as $table) {
            foreach ($map as $full => $abbr) {
                DB::table($table)->where('state', $full)->update(['state' => $abbr]);
            }
        }
    }

    private function backfillPoiCategoriesAndDropCategory(): void
    {
        foreach (DB::table('pois')->orderBy('id')->cursor() as $row) {
            $cat = $row->category ?? null;
            $payload = ['categories' => $cat ? [$cat] : ['Deep Roots']];
            DB::table('pois')->where('id', $row->id)->update([
                'categories' => json_encode($payload['categories']),
            ]);
        }

        Schema::table('pois', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
