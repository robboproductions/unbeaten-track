<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pois', function (Blueprint $table) {
            $table->string('narration_voice_label', 120)->nullable()->after('narration_voice_id');
        });

        Schema::table('towns', function (Blueprint $table) {
            $table->string('narration_voice_label', 120)->nullable()->after('narration_voice_id');
        });
    }

    public function down(): void
    {
        Schema::table('pois', function (Blueprint $table) {
            $table->dropColumn('narration_voice_label');
        });

        Schema::table('towns', function (Blueprint $table) {
            $table->dropColumn('narration_voice_label');
        });
    }
};
