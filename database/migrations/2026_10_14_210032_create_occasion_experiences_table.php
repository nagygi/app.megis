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
        Schema::table('materials', function (Blueprint $table) {
            // The occasion of a process the material belongs to (12 · 12.2); empty: "Egyikhez sem".
            $table->foreignId('process_occasion_id')->nullable()->constrained()->nullOnDelete();
            // "Egy helyzet": the user's own account of an occasion, spoken or typed (12 · 12.3).
            $table->boolean('is_situation')->default(false);
            // "A résztvevők tudnak a felvételről, és hozzájárultak." — who ticked it and when (12 · 12.2).
            $table->timestamp('recording_consent_at')->nullable();
            $table->foreignId('recording_consent_by')->nullable()->constrained('users')->nullOnDelete();
            // An unclassified material the admin marked as "Egyéb" (12 · 12.6).
            $table->timestamp('classified_other_at')->nullable();
            // The uploader answered "Hova tartozik?" (12 · 12.2); processing starts then.
            $table->timestamp('placed_at')->nullable();
        });

        // "A megélésed" after a situation or an upload (12 · 12.4): one per occasion and user.
        Schema::create('occasion_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_occasion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('main_diagnosis_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            // Row key (value:… / tree:…) => "1"…"6" or "dont_know".
            $table->json('answers')->nullable();
            // yes · no · partial · dont_know
            $table->string('effectiveness')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('value_set_version_id')->nullable()->constrained('versions')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();

            $table->unique(['process_occasion_id', 'user_id']);
        });

        Schema::table('process_occasions', function (Blueprint $table) {
            // Who gave the occasion at upload, when it did not come from the calendar (12 · 12.2).
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_occasions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
        Schema::dropIfExists('occasion_experiences');
        Schema::table('materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recording_consent_by');
            $table->dropConstrainedForeignId('process_occasion_id');
            $table->dropColumn(['is_situation', 'recording_consent_at', 'classified_other_at', 'placed_at']);
        });
    }
};
