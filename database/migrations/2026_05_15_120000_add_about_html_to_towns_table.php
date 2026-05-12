<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->longText('about_html')->nullable()->after('editorial_hook');
        });
    }

    public function down(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->dropColumn('about_html');
        });
    }
};
