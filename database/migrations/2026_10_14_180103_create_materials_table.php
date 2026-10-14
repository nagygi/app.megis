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
        // A recording, transcript or text the value measurement reads (06, later 12).
        // Files live on the private "materials" disk (06 · 6.6).
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('context');
            $table->string('source');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            // The owner: who recorded or uploaded it, or whose mirror it is.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('audio_mime')->nullable();
            $table->unsignedInteger('audio_seconds')->nullable();
            // Only the audio is deleted on request; the transcript stays (06 · 6.6).
            $table->timestamp('audio_deleted_at')->nullable();
            $table->longText('text')->nullable();
            // Speaker turns: [{speaker, start, end, text}].
            $table->json('segments')->nullable();
            // Admin upload: speaker label => user id, or null when skipped, and the
            // type and date of the diagnoses it becomes (06 · 6.3).
            $table->json('speaker_assignments')->nullable();
            $table->foreignId('diagnosis_type_id')->nullable()->constrained()->nullOnDelete();
            $table->date('diagnosed_on')->nullable();
            $table->string('status');
            $table->string('error', 1000)->nullable();
            $table->foreignId('transcription_ai_call_id')->nullable()->constrained('ai_calls')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'context']);
            $table->index('status');
        });

        // Step A: every value the engine found, raw, as the history of what recurs
        // and what the Értéktár misses (06 · 6.4).
        Schema::create('raw_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_call_id')->nullable()->constrained()->nullOnDelete();
            $table->string('speaker')->nullable();
            $table->text('quote');
            $table->string('free_name');
            $table->text('reasoning')->nullable();
            $table->unsignedTinyInteger('strength')->nullable();
            // "nem az enyém" (06 · 6.1, ET3): kept, but not used.
            $table->timestamp('excluded_at')->nullable();
            // The "AI által detektált" value a non-matching detection became (06 · 6.4).
            $table->foreignId('unmatched_value_id')->nullable()->constrained('values')->nullOnDelete();
            $table->timestamps();

            $table->index('unmatched_value_id');
        });

        // Step B: a detection matches zero, one or more Értéktár values (06 · 6.4).
        Schema::create('detection_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_detection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('value_id')->constrained()->restrictOnDelete();
            $table->foreignId('value_version_id')->nullable()->constrained('versions')->nullOnDelete();
            $table->decimal('confidence', 4, 3);
            $table->foreignId('ai_call_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['raw_detection_id', 'value_id']);
        });

        // Step C: "nincs adat" or 1–6 per matched value, per speaker (06 · 6.4).
        Schema::create('material_value_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->string('speaker')->nullable();
            $table->foreignId('value_id')->constrained()->restrictOnDelete();
            $table->foreignId('value_version_id')->nullable()->constrained('versions')->nullOnDelete();
            $table->string('state');
            $table->decimal('score', 3, 1)->nullable();
            $table->text('reasoning')->nullable();
            $table->json('detection_ids');
            $table->foreignId('ai_call_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['material_id', 'speaker']);
        });

        // What is left of an anonymous mirror after 24 hours: the free name and
        // the match, without any quote (06 · 6.1).
        Schema::create('anonymous_value_stats', function (Blueprint $table) {
            $table->id();
            $table->string('free_name');
            $table->foreignId('value_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->date('recorded_on');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anonymous_value_stats');
        Schema::dropIfExists('material_value_scores');
        Schema::dropIfExists('detection_matches');
        Schema::dropIfExists('raw_detections');
        Schema::dropIfExists('materials');
    }
};
