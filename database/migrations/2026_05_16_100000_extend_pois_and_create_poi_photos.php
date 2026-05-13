<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pois', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('detour_km');
            $table->decimal('longitude', 11, 7)->nullable()->after('latitude');
            $table->longText('about_html')->nullable()->after('short_description');
            $table->text('spreadsheet_notes')->nullable()->after('about_html');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->foreignId('published_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('poi_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poi_id')->constrained('pois')->cascadeOnDelete();
            $table->string('path', 512);
            $table->text('caption')->nullable();
            $table->string('source', 500)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poi_photos');

        Schema::table('pois', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn([
                'latitude',
                'longitude',
                'about_html',
                'spreadsheet_notes',
                'published_at',
            ]);
        });
    }
};
