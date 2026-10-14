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
        // A leader's calendar, read for 90 days and refreshed daily until the time window ends (09).
        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('main_diagnosis_id')->nullable()->constrained()->nullOnDelete();
            // google · microsoft · ics_url · ics_file
            $table->string('provider');
            // Tokens or the secret iCal address, encrypted (09 · 9.2).
            $table->text('credentials')->nullable();
            // active · broken · disconnected
            $table->string('status');
            $table->timestamp('connected_at');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            // The event metadata lives 90 days from connecting (09 · 9.5).
            $table->timestamp('metadata_expires_at');
            $table->text('error')->nullable();
            // "142 esemény · 90 nap", and what did not form a pattern (09 · 9.3).
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedInteger('one_off_count')->default(0);
            $table->unsignedInteger('unlinked_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'metadata_expires_at']);
        });

        // Event metadata: title, time, series, attendees, description. Never
        // attachments or private entries (09 · 9.1). Deleted after 90 days.
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_connection_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('series_key')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->json('attendees')->nullable();
            $table->timestamps();

            $table->unique(['calendar_connection_id', 'external_id']);
            $table->index(['calendar_connection_id', 'series_key']);
        });

        // The four questions of the conversation and what was heard in them (10).
        Schema::create('process_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('main_diagnosis_id')->nullable()->constrained()->nullOnDelete();
            // question number => answer
            $table->json('answers')->nullable();
            // question number => the chips extracted from the answer (10 · 10.2)
            $table->json('extracts')->nullable();
            // question number => the answer the extract was made of, to know when it is outdated
            $table->json('extracted_answers')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_conversations');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('calendar_connections');
    }
};
