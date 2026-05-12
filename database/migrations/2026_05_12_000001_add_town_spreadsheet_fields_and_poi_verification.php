<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->unsignedInteger('population_approx')->nullable()->after('region');
            $table->decimal('latitude', 10, 7)->nullable()->after('population_approx');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->boolean('has_pub')->default(false)->after('longitude');
            $table->boolean('has_cafe')->default(false)->after('has_pub');
            $table->boolean('has_shop')->default(false)->after('has_cafe');
            $table->boolean('has_fuel')->default(false)->after('has_shop');
            $table->boolean('has_caravan_park')->default(false)->after('has_fuel');
            $table->text('editorial_hook')->nullable()->after('has_caravan_park');
            $table->string('likely_poi_categories', 255)->nullable()->after('editorial_hook');
            $table->string('suggested_corridor', 255)->nullable()->after('likely_poi_categories');
            $table->text('spreadsheet_notes')->nullable()->after('suggested_corridor');
        });

        Schema::table('pois', function (Blueprint $table) {
            $table->string('verification_status', 40)->default('not_verified')->after('status');
            $table->date('verified_at')->nullable()->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('pois', function (Blueprint $table) {
            $table->dropColumn(['verification_status', 'verified_at']);
        });

        Schema::table('towns', function (Blueprint $table) {
            $table->dropColumn([
                'population_approx',
                'latitude',
                'longitude',
                'has_pub',
                'has_cafe',
                'has_shop',
                'has_fuel',
                'has_caravan_park',
                'editorial_hook',
                'likely_poi_categories',
                'suggested_corridor',
                'spreadsheet_notes',
            ]);
        });
    }
};
