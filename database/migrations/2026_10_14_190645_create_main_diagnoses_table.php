<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // What a 04 diagnosis needs to run (17): subject, leader, time window, stage.
        Schema::create('main_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->unique()->constrained()->cascadeOnDelete();
            // team · individual (a private person) · person (one user with the company's values) (17 · 17.2)
            $table->string('subject');
            // The product at the start, so a later change of the organization's product does not touch it (17 · 17.1).
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_key');
            $table->string('product_name');
            $table->foreignId('leader_id')->constrained('users')->restrictOnDelete();
            $table->string('stage');
            // The leader's deadline; empty means "egyeztetés alatt" (17 · 17.2).
            $table->date('starts_on')->nullable();
            $table->date('publishes_on');
            $table->boolean('show_peer_quotes')->default(true);
            // The value set version the diagnosis measures against, recorded at "Fut" (08 · 8.4, plan 15 · 1).
            $table->foreignId('value_set_version_id')->nullable()->constrained('versions')->restrictOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('running_at')->nullable();
            $table->timestamp('reading_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['leader_id', 'stage']);
        });

        // The leader and the peers of a main diagnosis (17 · 17.2, 25).
        Schema::create('main_diagnosis_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // leader · peer
            $table->string('role');
            // A team member taken off the peer list on A1 (17 · 17.2).
            $table->timestamp('excluded_at')->nullable();
            // A replaced leader keeps their answers (17 · 17.7).
            $table->timestamp('replaced_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            // The first signed-in view of V1 or T1, never the link itself (plan 3.2).
            $table->timestamp('opened_at')->nullable();
            // "Kezdem" on T1 (25 · 25.2).
            $table->timestamp('started_at')->nullable();
            // Autosaved V5 / T4 answers until they are sent (08 · 8.3).
            $table->json('experience_draft')->nullable();
            // The own values as they were at publishing (24, question 5; 21 · 21.3).
            $table->foreignId('personal_value_set_version_id')->nullable()->constrained('versions')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['main_diagnosis_id', 'user_id', 'role']);
            $table->index(['user_id', 'role']);
        });

        // "Megnézte" and "kihagyta"; whether a step is done comes from its data (plan 5).
        Schema::create('main_diagnosis_step_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('step');
            // viewed · skipped
            $table->string('mark');
            $table->timestamps();

            $table->unique(['main_diagnosis_id', 'user_id', 'step']);
        });

        // A peer's consents with the text they agreed to (25 · 25.2, 31 · 31.5, 31.6).
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            // answers (required) · quotes (optional)
            $table->string('kind');
            $table->string('text_version');
            $table->text('text');
            $table->timestamp('given_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'main_diagnosis_id', 'kind']);
        });

        // "Fő diagnózis": a new starting type that cannot be deleted (17 · 17.1).
        // A type an admin already created with this name becomes the starting type.
        DB::table('diagnosis_types')->updateOrInsert(
            ['name' => 'Fő diagnózis'],
            ['key' => 'main', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('diagnosis_types')->where('key', 'main')->delete();
        Schema::dropIfExists('consents');
        Schema::dropIfExists('main_diagnosis_step_marks');
        Schema::dropIfExists('main_diagnosis_participants');
        Schema::dropIfExists('main_diagnoses');
    }
};
