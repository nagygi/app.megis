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
        // A pattern of the Mintatár (NM-19): how a capability, a precondition or a
        // detector error shows in real material. The mégis Sheet is the master.
        Schema::create('patterns', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('type');
            $table->string('subtype')->nullable();
            $table->string('name');
            $table->string('level')->nullable();
            $table->string('level_marker')->nullable();
            $table->boolean('externally_visible')->nullable();
            $table->jsonb('products');
            $table->string('validity');
            $table->string('origin')->nullable();
            // The text fields of the Sheet by key (Pattern::CONTENT_FIELDS).
            $table->jsonb('content');
            // "hol látszik": the kinds of source the pattern shows in.
            $table->jsonb('where_visible');
            // An "előfeltétel" cell without an E-code.
            $table->text('precondition_note')->nullable();
            $table->jsonb('proposed_fields');
            $table->jsonb('field_states');
            // Counts the Sheet computes from its Találat and Kapcsolat sheets; every
            // load overwrites them, they make no version.
            $table->string('sheet_maturity')->nullable();
            $table->unsignedInteger('sheet_approved_clients')->default(0);
            $table->unsignedInteger('sheet_proposed_findings')->default(0);
            $table->unsignedInteger('sheet_false_alarms')->default(0);
            $table->unsignedInteger('sheet_evidence_count')->default(0);
            $table->text('search_text');
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'validity']);
        });

        // "kapcsolt képesség(ek)" with "illeszkedés" (pontos / részleges).
        Schema::create('capability_pattern', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capability_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pattern_id')->constrained()->cascadeOnDelete();
            $table->string('fit')->nullable();
            $table->text('fit_note')->nullable();
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['capability_id', 'pattern_id']);
            $table->index('pattern_id');
        });

        Schema::create('pattern_precondition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pattern_id')->constrained()->cascadeOnDelete();
            $table->foreignId('precondition_id')->constrained()->restrictOnDelete();
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['pattern_id', 'precondition_id']);
            $table->index('precondition_id');
        });

        // The Szabálykönyv of the mégis (NY, AL, KO, ER, PR, VE, EF, FE), read only.
        Schema::create('capability_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('area')->nullable();
            $table->text('text');
            $table->string('status')->nullable();
            $table->string('status_label')->nullable();
            $table->text('source')->nullable();
            $table->string('version')->nullable();
            $table->string('decided_on')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::table('capabilities', function (Blueprint $table) {
            $table->unsignedInteger('sheet_confirmed_findings')->nullable();
            $table->unsignedInteger('sheet_linked_evidence')->nullable();
        });

        Schema::table('capability_imports', function (Blueprint $table) {
            $table->unsignedInteger('patterns_created_count')->default(0);
            $table->unsignedInteger('patterns_updated_count')->default(0);
            $table->unsignedInteger('patterns_unchanged_count')->default(0);
            $table->unsignedInteger('rules_changed_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capability_imports', function (Blueprint $table) {
            $table->dropColumn(['patterns_created_count', 'patterns_updated_count', 'patterns_unchanged_count', 'rules_changed_count']);
        });

        Schema::table('capabilities', function (Blueprint $table) {
            $table->dropColumn(['sheet_confirmed_findings', 'sheet_linked_evidence']);
        });

        Schema::dropIfExists('capability_rules');
        Schema::dropIfExists('pattern_precondition');
        Schema::dropIfExists('capability_pattern');
        Schema::dropIfExists('patterns');
    }
};
