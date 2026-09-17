<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('species')->nullable();
            $table->string('botanical_family')->nullable();
            $table->string('identification', 64);
            $table->text('notes')->nullable();
            $table->boolean('favorite')->default(false);
            $table->timestamps();

            $table->unique('identification');
        });

        Schema::create('plant_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('plant_id')->constrained('plants')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('r2_key', 512)->nullable();
            $table->boolean('is_main')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('plant_videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('plant_id')->constrained('plants')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('r2_key', 512)->nullable();
            $table->string('poster_url', 2048)->nullable();
            $table->string('poster_r2_key', 512)->nullable();
            $table->unsignedSmallInteger('duration_seconds')->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_videos');
        Schema::dropIfExists('plant_photos');
        Schema::dropIfExists('plants');
    }
};
