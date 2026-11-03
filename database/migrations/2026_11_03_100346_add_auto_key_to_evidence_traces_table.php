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
        // The calculation's own traces without a detection: the calendar and the V11·c notes
        // of a process row ("calendar", "experience:{id}"), kept and cleared by this key (18 · 18.2).
        Schema::table('evidence_traces', function (Blueprint $table) {
            $table->string('auto_key')->nullable()->after('raw_detection_id');
            $table->index(['diagnosis_row_id', 'auto_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidence_traces', function (Blueprint $table) {
            $table->dropIndex(['diagnosis_row_id', 'auto_key']);
            $table->dropColumn('auto_key');
        });
    }
};
