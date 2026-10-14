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
        // One row per AI call (16 · 16.3, 16.4; NFR · 11). The request and the
        // answer are not stored: they may hold personal data.
        Schema::create('ai_calls', function (Blueprint $table) {
            $table->id();
            $table->string('task');
            $table->string('provider');
            $table->string('model');
            // The exact model id the API returned.
            $table->string('model_version')->nullable();
            $table->string('prompt')->nullable();
            $table->unsignedInteger('prompt_version')->nullable();
            $table->string('status');
            $table->string('error', 1000)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->decimal('audio_minutes', 8, 2)->nullable();
            // The organization stays readable after it is deleted (16 · 16.4).
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('organization_name')->nullable();
            $table->boolean('public_mirror')->default(false);
            // Cost at the time of the call; null when the model has no price.
            $table->decimal('cost_usd', 12, 6)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            // The result the call belongs to (16 · 16.3).
            $table->nullableMorphs('subject');
            $table->timestamp('created_at')->index();

            $table->index(['task', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_calls');
    }
};
