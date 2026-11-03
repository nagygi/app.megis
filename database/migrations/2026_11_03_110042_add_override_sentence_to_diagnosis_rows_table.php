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
        // "Átírom": the sentence of the row written over by the mégis, in place of the template's gap sentence (20 · 20.2).
        Schema::table('diagnosis_rows', function (Blueprint $table) {
            $table->string('override_sentence', 255)->nullable()->after('override_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnosis_rows', function (Blueprint $table) {
            $table->dropColumn('override_sentence');
        });
    }
};
