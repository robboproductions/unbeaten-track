<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('town_photos', function (Blueprint $table) {
            $table->text('caption')->nullable()->after('path');
            $table->string('source', 500)->nullable()->after('caption');
        });
    }

    public function down(): void
    {
        Schema::table('town_photos', function (Blueprint $table) {
            $table->dropColumn(['caption', 'source']);
        });
    }
};
