<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Unit prices per model in USD, refreshed by megis:prices:sync (16 · 16.4):
        // per million tokens, and per minute of audio.
        Schema::create('ai_model_prices', function (Blueprint $table) {
            $table->id();
            $table->string('model')->unique();
            $table->string('provider');
            $table->decimal('input', 12, 6);
            $table->decimal('output', 12, 6);
            $table->decimal('cache_read', 12, 6)->nullable();
            $table->decimal('cache_write', 12, 6)->nullable();
            $table->decimal('audio_minute', 12, 6)->nullable();
            // The entry of the price list the prices come from.
            $table->string('source_key');
            $table->timestamp('synced_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_model_prices');
    }
};
