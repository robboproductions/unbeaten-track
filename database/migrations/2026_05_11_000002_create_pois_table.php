<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pois', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 32);
            $table->foreignId('town_id')->constrained()->cascadeOnDelete();
            $table->string('state', 8);
            $table->string('status', 16)->default('draft');
            $table->decimal('detour_km', 6, 1)->nullable();
            $table->string('short_description', 180)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pois');
    }
};

