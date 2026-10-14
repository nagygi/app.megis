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
        Schema::table('diagnoses', function (Blueprint $table) {
            // The main diagnosis this one belongs to (17 · 17.4); it is approved and published with it.
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('diagnoses')->restrictOnDelete();
            // The organization's value set version a Megélés diagnosis answered (08 · 8.3, 8.4).
            $table->foreignId('value_set_version_id')->nullable()->constrained('versions')->restrictOnDelete();
            // Taken out of the main diagnosis when the peer withdrew the consent (25 · 25.6).
            $table->timestamp('excluded_at')->nullable();

            $table->index(['parent_id', 'diagnosis_type_id']);
        });

        Schema::table('diagnosis_transitions', function (Blueprint $table) {
            // Main diagnoses also log their stage (17 · 17.5).
            $table->string('from_stage')->nullable();
            $table->string('to_stage')->nullable();
        });

        Schema::table('materials', function (Blueprint $table) {
            // The main diagnosis a mirror or an upload belongs to (17 · 17.4, 17.6).
            $table->foreignId('parent_diagnosis_id')->nullable()->constrained('diagnoses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_diagnosis_id');
        });

        Schema::table('diagnosis_transitions', function (Blueprint $table) {
            $table->dropColumn(['from_stage', 'to_stage']);
        });

        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'diagnosis_type_id']);
            $table->dropConstrainedForeignId('value_set_version_id');
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('excluded_at');
        });
    }
};
