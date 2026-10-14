<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The reading (20): who approved each row, claim and text and when, the
 * traces taken out, highlighted or added by hand, the biggest gap as
 * decided, and the log of every step (20 · 20.5).
 */
return new class extends Migration
{
    public function up(): void
    {
        // See the calculation migration: SQLite trips over the "values" reference when rebuilding.
        $withKeys = DB::getDriverName() !== 'sqlite';

        Schema::table('diagnosis_rows', function (Blueprint $table) use ($withKeys) {
            $table->timestamp('approved_at')->nullable();
            $approvedBy = $table->foreignId('approved_by')->nullable();

            if ($withKeys) {
                $approvedBy->constrained('users')->nullOnDelete();
            }
        });

        foreach (['diagnosis_claims', 'diagnosis_texts'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        Schema::table('evidence_traces', function (Blueprint $table) {
            // Out of the report; the raw detection stays (20 · 20.2).
            $table->timestamp('excluded_at')->nullable();
            // Shown first on the chapter page (20 · 20.2).
            $table->timestamp('highlighted_at')->nullable();
            // A trace written or chosen by hand stays when the row is recalculated.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('main_diagnoses', function (Blueprint $table) {
            // "A legnagyobb rés": accepted, or another row chosen (20 · 20.4).
            $table->foreignId('biggest_gap_row_id')->nullable()->constrained('diagnosis_rows')->nullOnDelete();
            $table->timestamp('biggest_gap_approved_at')->nullable();
            $table->foreignId('biggest_gap_approved_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('review_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('subject');
            // approved · overridden · scored · reopened · fell_back · excluded · included · highlighted · added · edited · deleted · rejected
            $table->string('action');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['diagnosis_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_events');

        Schema::table('main_diagnoses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('biggest_gap_approved_by');
            $table->dropConstrainedForeignId('biggest_gap_row_id');
            $table->dropColumn('biggest_gap_approved_at');
        });

        Schema::table('evidence_traces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['excluded_at', 'highlighted_at']);
        });

        foreach (['diagnosis_claims', 'diagnosis_texts'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('approved_by');
                $table->dropColumn('approved_at');
            });
        }

        Schema::table('diagnosis_rows', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['approved_by']);
            }

            $table->dropColumn(['approved_by', 'approved_at']);
        });
    }
};
