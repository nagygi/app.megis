<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The frozen report (21 · 21.3, 21.4): a snapshot at publishing and at every
 * correction after it, the "frissítve" marks of the chapters, and the
 * leader's "Magatokra ismertek?" answers (28 · 28.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Why a published diagnosis was withdrawn (21 · 21.5).
        Schema::table('diagnosis_transitions', function (Blueprint $table) {
            $table->text('reason')->nullable();
        });

        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            // published · correction · republished
            $table->string('reason');
            $table->text('note')->nullable();
            $table->json('data');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['main_diagnosis_id', 'sequence']);
        });

        Schema::create('chapter_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            // values · processes · areas · brand
            $table->string('chapter');
            // correction · feedback
            $table->string('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['main_diagnosis_id', 'chapter']);
        });

        Schema::create('report_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('chapter');
            // yes · partial · no
            $table->string('answer');
            $table->text('text')->nullable();
            // A spoken answer, transcribed (28 · 28.1).
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('incorporated_at')->nullable();
            $table->foreignId('incorporated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['main_diagnosis_id', 'chapter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_feedback');
        Schema::dropIfExists('chapter_updates');
        Schema::dropIfExists('report_snapshots');

        Schema::table('diagnosis_transitions', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
