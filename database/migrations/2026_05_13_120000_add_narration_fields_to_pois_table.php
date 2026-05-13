<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pois', function (Blueprint $table) {
            // Must not use after('spreadsheet_notes'): that column is added in a later migration (2026_05_16_100000).
            $table->text('narration_script')->nullable()->after('short_description');
            $table->string('narration_voice_id', 64)->nullable()->after('narration_script');
            $table->string('narration_model_id', 64)->nullable()->after('narration_voice_id');
            $table->string('narration_audio_path', 512)->nullable()->after('narration_model_id');
            $table->unsignedInteger('narration_audio_duration_seconds')->nullable()->after('narration_audio_path');
            $table->unsignedInteger('narration_audio_bytes')->nullable()->after('narration_audio_duration_seconds');
            $table->string('narration_script_hash', 64)->nullable()->after('narration_audio_bytes');
            $table->timestamp('narration_generated_at')->nullable()->after('narration_script_hash');
            $table->foreignId('narration_generated_by')->nullable()->after('narration_generated_at')->constrained('users')->nullOnDelete();

            $table->index('narration_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('pois', function (Blueprint $table) {
            $table->dropIndex(['narration_generated_at']);
            $table->dropConstrainedForeignId('narration_generated_by');
            $table->dropColumn([
                'narration_script',
                'narration_voice_id',
                'narration_model_id',
                'narration_audio_path',
                'narration_audio_duration_seconds',
                'narration_audio_bytes',
                'narration_script_hash',
                'narration_generated_at',
            ]);
        });
    }
};
