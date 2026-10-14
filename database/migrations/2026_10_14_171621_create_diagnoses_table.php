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
        // A diagnosis records which values, and how much, are typical of an
        // organization, team or user (04). Main diagnoses (17) build on it.
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('level');
            $table->foreignId('team_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('diagnosis_type_id')->constrained()->restrictOnDelete();
            $table->date('diagnosed_on');
            // Set when "Időszak" is on (04 · 4.3).
            $table->date('diagnosed_until')->nullable();
            $table->string('creator_kind');
            $table->string('status');
            // How the aggregated data was calculated, saved with the diagnosis (04 · 4.5).
            $table->jsonb('aggregation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'level', 'status']);
            $table->index(['user_id', 'status']);
        });

        // One row per value, and the four fixed rows of the brand base (04 · 4.4).
        Schema::create('diagnosis_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->foreignId('value_id')->nullable()->constrained()->restrictOnDelete();
            // The Értéktár version in use when the value was assigned (03 · 3.3).
            $table->foreignId('value_version_id')->nullable()->constrained('versions')->restrictOnDelete();
            $table->string('tree_element')->nullable();
            $table->unsignedInteger('position')->default(0);
            // PHI 1–6; "–" (no trace) is a state, never a number (04, 18 · 18.4).
            $table->decimal('score', 3, 1)->nullable();
            $table->string('score_state')->nullable();
            $table->decimal('experience', 3, 1)->nullable();
            $table->string('experience_state')->nullable();
            $table->text('experience_met_example')->nullable();
            $table->text('experience_unmet_example')->nullable();
            $table->text('reasoning')->nullable();
            $table->date('assigned_on');
            // n, standard deviation and warnings of an aggregated row (04 · 4.5).
            $table->json('aggregation_stats')->nullable();
            $table->boolean('manually_modified')->default(false);
            $table->timestamps();

            $table->unique(['diagnosis_id', 'value_id']);
            $table->unique(['diagnosis_id', 'tree_element']);
        });

        // Every status change: who and when (04 · 4.3).
        Schema::create('diagnosis_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnosis_transitions');
        Schema::dropIfExists('diagnosis_rows');
        Schema::dropIfExists('diagnoses');
    }
};
