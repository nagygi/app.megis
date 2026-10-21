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
        // A trace ("nyom", NM-12): a verbatim part of a material that may show a
        // capability or a precondition. Client data: it goes with the material.
        Schema::create('capability_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_call_id')->nullable()->constrained()->nullOnDelete();
            $table->string('speaker')->nullable();
            $table->string('speaker_role')->nullable();
            $table->text('quote');
            $table->string('location')->nullable();
            $table->string('subject_level');
            $table->boolean('boundary')->default(false);
            $table->string('direction');
            $table->string('directness');
            $table->boolean('is_raw')->default(true);
            // Marks that weaken a trace: led answer, third person, old event…
            $table->jsonb('flags');
            // E1–E9 the trace speaks about: [{code, direction}].
            $table->jsonb('preconditions');
            $table->jsonb('suggested_capability_ids');
            // Detector-error patterns that block the trace: [{pattern_id, reason}].
            $table->jsonb('detector_blocks');
            $table->text('alternative_reading')->nullable();
            $table->string('candidate')->nullable();
            $table->text('candidate_note')->nullable();
            $table->date('recorded_on')->nullable();
            $table->timestamp('excluded_at')->nullable();
            $table->foreignId('excluded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['material_id', 'speaker']);
        });

        // A finding ("találat"): a pattern of a capability in one material, with its traces.
        Schema::create('capability_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('capability_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pattern_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pattern_version_id')->nullable()->constrained('versions')->nullOnDelete();
            $table->string('kind');
            $table->string('subject_level');
            $table->string('status');
            $table->string('machine_confidence');
            $table->text('reasoning')->nullable();
            // NY-26: the first finding of a hypothesis pattern is approved one by one.
            $table->boolean('needs_first_approval')->default(false);
            $table->foreignId('ai_call_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['material_id', 'capability_id']);
            $table->index('pattern_id');
        });

        Schema::create('capability_finding_trace', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capability_finding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('capability_trace_id')->constrained()->cascadeOnDelete();

            $table->unique(['capability_finding_id', 'capability_trace_id']);
            $table->index('capability_trace_id');
        });

        // The state of a capability at a level in a diagnosis (6.2, 6.3): the
        // automatic one from the findings, and the final one an admin may set.
        Schema::create('capability_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('capability_id')->constrained()->cascadeOnDelete();
            $table->foreignId('capability_version_id')->nullable()->constrained('versions')->nullOnDelete();
            $table->string('subject_level');
            $table->string('automatic_state');
            $table->string('automatic_confidence')->nullable();
            // The rules that decided, with the counts (KO-03, KO-05 …).
            $table->jsonb('automatic_basis');
            $table->string('state');
            $table->string('confidence')->nullable();
            $table->boolean('uneven')->default(false);
            $table->foreignId('blocker_precondition_id')->nullable()->constrained('preconditions')->nullOnDelete();
            $table->text('blocker_note')->nullable();
            $table->text('override_reason')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['diagnosis_id', 'capability_id', 'subject_level']);
            $table->index('capability_id');
        });

        // E1–E9 in a diagnosis (6.7): megvan / részben / nincs / nem tudjuk, not scored.
        Schema::create('capability_precondition_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('precondition_id')->constrained()->restrictOnDelete();
            $table->string('automatic_value');
            $table->string('value');
            $table->text('note')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['diagnosis_id', 'precondition_id']);
            $table->index('precondition_id');
        });

        // A nameless log of the decisions on findings, per pattern (NY-27): the
        // pattern's counters outlive a client's deleted data.
        Schema::create('pattern_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pattern_id')->constrained()->cascadeOnDelete();
            $table->string('organization_key', 64);
            $table->string('outcome');
            $table->date('observed_on');
            $table->timestamps();

            $table->index(['pattern_id', 'outcome']);
        });

        Schema::table('patterns', function (Blueprint $table) {
            $table->unsignedInteger('approved_organizations')->default(0);
            $table->unsignedInteger('suggestions')->default(0);
            $table->unsignedInteger('false_alarms')->default(0);
            $table->timestamp('review_flagged_at')->nullable();
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->string('capability_status')->nullable();
            $table->text('capability_error')->nullable();
            $table->timestamp('capability_processed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['capability_status', 'capability_error', 'capability_processed_at']);
        });

        Schema::table('patterns', function (Blueprint $table) {
            $table->dropColumn(['approved_organizations', 'suggestions', 'false_alarms', 'review_flagged_at']);
        });

        Schema::dropIfExists('pattern_observations');
        Schema::dropIfExists('capability_precondition_assessments');
        Schema::dropIfExists('capability_assessments');
        Schema::dropIfExists('capability_finding_trace');
        Schema::dropIfExists('capability_findings');
        Schema::dropIfExists('capability_traces');
    }
};
