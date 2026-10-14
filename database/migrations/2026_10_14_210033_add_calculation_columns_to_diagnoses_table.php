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
        // The five areas of a Teljes diagnosis (18 · 18.1); the texts are edited in Beállítások → Területek.
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            // "Mit nézünk", "nyoma" (what we read it from) and the description of a 6 (18 · 18.1).
            $table->text('what_we_look_at')->nullable();
            $table->text('trace')->nullable();
            $table->text('six_description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        $position = 0;

        foreach ([
            'strategy' => 'Stratégia',
            'culture' => 'Kultúra és közösség',
            'value_proposition' => 'Értékajánlat',
            'communication' => 'Kommunikáció',
            'growth' => 'Növekedés és tanulás',
        ] as $key => $name) {
            DB::table('areas')->insert(['key' => $key, 'name' => $name, 'position' => $position++, 'created_at' => $now, 'updated_at' => $now]);
        }

        Schema::table('diagnoses', function (Blueprint $table) {
            // A Folyamat level diagnosis is about one process (13 · 13.1).
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            // The point of a process diagnosis in its header (13 · 13.1); hand-written with a reason (13 · 13.5).
            $table->decimal('score', 3, 1)->nullable();
            $table->string('score_state')->nullable();
            $table->decimal('automatic_score', 3, 1)->nullable();
            $table->string('automatic_score_state')->nullable();
            $table->text('score_override_reason')->nullable();
            $table->foreignId('score_overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('score_overridden_at')->nullable();
        });

        // SQLite rebuilds the table to add a foreign key and trips over the "values"
        // reference; the keys are added on PostgreSQL only (the tests run on SQLite).
        $withKeys = DB::getDriverName() !== 'sqlite';

        Schema::table('diagnosis_rows', function (Blueprint $table) use ($withKeys) {
            // Rows of a main diagnosis: processes (13) and areas (18 · 18.1).
            $process = $table->foreignId('process_id')->nullable();
            $area = $table->foreignId('area_id')->nullable();
            // What the calculation says when the row was written by hand (13 · 13.4, 13.5; 20).
            $table->decimal('automatic_score', 3, 1)->nullable();
            $table->string('automatic_score_state')->nullable();
            $table->text('override_reason')->nullable();
            $overriddenBy = $table->foreignId('overridden_by')->nullable();
            $table->timestamp('overridden_at')->nullable();

            if ($withKeys) {
                $process->constrained()->nullOnDelete();
                $area->constrained()->nullOnDelete();
                $overriddenBy->constrained('users')->nullOnDelete();
            }
        });

        // A trace of a row: a quote or an observation with its source (18 · 18.2).
        Schema::create('evidence_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_row_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('process_occasion_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('raw_detection_id')->nullable()->constrained()->nullOnDelete();
            $table->text('quote');
            // conversation · recording · note · document · mirror · calendar · experience
            $table->string('source_type');
            // leader · peer · external
            $table->string('role')->nullable();
            // Only the admin sees the name (18 · 18.2).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('recorded_on')->nullable();
            $table->timestamps();
        });

        // A sentence of the report with its evidence (18 · 18.3); AI draft first, approved in reading (20).
        Schema::create('diagnosis_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnosis_row_id')->constrained()->cascadeOnDelete();
            // strength · crack · hunch
            $table->string('kind');
            $table->text('text');
            // draft · approved · rejected
            $table->string('status')->default('draft');
            $table->json('trace_ids')->nullable();
            $table->foreignId('ai_call_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // The reading's texts: the summary per block, the brand base, the mégis recommendation (18 · 18.3).
        Schema::create('diagnosis_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('text');
            $table->string('status')->default('draft');
            // The recommendation's row and the point it would move (18 · 18.3).
            $table->json('meta')->nullable();
            $table->foreignId('ai_call_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['diagnosis_id', 'key']);
        });

        Schema::table('main_diagnoses', function (Blueprint $table) {
            // "Csapat-szám nélkül megy" (19 · 19.4, 18 · 18.6), revocable until publishing.
            $table->timestamp('threshold_overridden_at')->nullable();
            $table->foreignId('threshold_overridden_by')->nullable()->constrained('users')->nullOnDelete();
            // "Várok 5 napot" (19 · 19.4).
            $table->date('threshold_snoozed_until')->nullable();
            // The ring %, the step, the biggest gap and the measuring points (18 · 18.3, 18.4).
            $table->json('calculation')->nullable();
            $table->timestamp('calculated_at')->nullable();
        });

        // "Beszélgetés a mégisszel" uploads are diagnoses of this type (19 · 19.5).
        DB::table('diagnosis_types')->updateOrInsert(
            ['name' => 'Beszélgetés a mégisszel'],
            ['key' => 'conversation', 'created_at' => $now, 'updated_at' => $now],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('diagnosis_types')->where('key', 'conversation')->delete();

        Schema::table('main_diagnoses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('threshold_overridden_by');
            $table->dropColumn(['threshold_overridden_at', 'threshold_snoozed_until', 'calculation', 'calculated_at']);
        });
        Schema::dropIfExists('diagnosis_texts');
        Schema::dropIfExists('diagnosis_claims');
        Schema::dropIfExists('evidence_traces');
        Schema::table('diagnosis_rows', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['overridden_by']);
                $table->dropForeign(['area_id']);
                $table->dropForeign(['process_id']);
            }

            $table->dropColumn(['process_id', 'area_id', 'overridden_by', 'automatic_score', 'automatic_score_state', 'override_reason', 'overridden_at']);
        });
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('score_overridden_by');
            $table->dropConstrainedForeignId('process_id');
            $table->dropColumn(['score', 'score_state', 'automatic_score', 'automatic_score_state', 'score_override_reason', 'score_overridden_at']);
        });
        Schema::dropIfExists('areas');
    }
};
