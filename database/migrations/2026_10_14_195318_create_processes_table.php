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
        // A recurring process of the organization (11); a diagnosis records which ones it measures.
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            // At most two levels: process → subprocess (11 · 11.2).
            $table->foreignId('parent_id')->nullable()->constrained('processes')->cascadeOnDelete();
            $table->string('name');
            // weekly · biweekly · monthly · quarterly · occasional
            $table->string('rhythm')->nullable();
            // "keddenként 9:00", from the calendar (09 · 9.3).
            $table->string('rhythm_label')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('headcount')->nullable();
            // "8/12 hét", "6/6 alkalom" (09 · 9.3).
            $table->string('occurrence_label')->nullable();
            // routine (measured) · one_off (a trace, no score) · excluded ("nem mérjük") (11 · 11.2)
            $table->string('kind')->default('routine');
            // calendar · conversation · manual
            $table->string('source');
            // high · medium · low · manual · conversation
            $table->string('confidence');
            // A "tisztázandó" from the conversation, in the "Bizonytalan" group (10 · 10.3).
            $table->boolean('unclear')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Named in the answer to the fourth question; only the admin sees it (10 · 10.2).
            $table->boolean('breakpoint_candidate')->default(false);
            // The calendar series the occasions come from (09 · 9.4, 9.5).
            $table->string('calendar_series_key')->nullable();
            // A calendar pattern that may be the same as a process from the conversation (10 · 10.4).
            $table->foreignId('merge_candidate_id')->nullable()->constrained('processes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'kind']);
        });

        // The trace of renaming: old name, who and when (11 · 11.2).
        Schema::create('process_renames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->string('old_name');
            $table->string('new_name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
        });

        // Participants: users of the organization, or "külsős" by name (11 · 11.3).
        Schema::create('process_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            // calendar · manual
            $table->string('source')->default('manual');
            $table->timestamps();
        });

        Schema::create('process_team', function (Blueprint $table) {
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();

            $table->primary(['process_id', 'team_id']);
        });

        // Own measures of a process; they never go into the PHI (11 · 11.3).
        Schema::create('process_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit')->nullable();
            // weekly · monthly · quarterly
            $table->string('rhythm');
            $table->string('target')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('process_metric_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_metric_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 14, 2);
            $table->date('measured_on');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // An occasion of a process; the date stays when the calendar metadata goes (09 · 9.4, 9.5; 12).
        Schema::create('process_occasions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            // The calendar event it came from, while its metadata lives.
            $table->unsignedBigInteger('calendar_event_id')->nullable();
            $table->string('calendar_event_key')->nullable();
            $table->dateTime('occurred_at');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('participant_count')->nullable();
            // calendar · manual
            $table->string('source');
            $table->timestamps();

            $table->unique(['process_id', 'calendar_event_key']);
        });

        // The processes a main diagnosis measures; the snapshot limit counts here (11, 18 · 18.1).
        Schema::create('main_diagnosis_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['main_diagnosis_id', 'process_id']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            // "A mégis javaslata" on V6·f, written by the admin (11 · 11.6).
            $table->string('process_recommendation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('process_recommendation');
        });
        Schema::dropIfExists('main_diagnosis_processes');
        Schema::dropIfExists('process_occasions');
        Schema::dropIfExists('process_metric_values');
        Schema::dropIfExists('process_metrics');
        Schema::dropIfExists('process_team');
        Schema::dropIfExists('process_participants');
        Schema::dropIfExists('process_renames');
        Schema::dropIfExists('processes');
    }
};
