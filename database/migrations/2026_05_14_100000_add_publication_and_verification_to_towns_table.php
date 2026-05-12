<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('status');
            $table->foreignId('published_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->string('verification_status', 32)->default('unverified')->after('published_by');
            $table->timestamp('verified_at')->nullable()->after('verification_status');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['published_at', 'verification_status', 'verified_at']);
        });
    }
};
