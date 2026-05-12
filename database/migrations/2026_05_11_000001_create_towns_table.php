<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('towns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('state', 8);
            $table->string('region')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('towns');
    }
};

