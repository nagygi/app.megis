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
        // An automatic diagnosis points to its source file and speaker (06 · 6.3, 6.5).
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->foreignId('material_id')->nullable()->after('aggregation')->constrained()->nullOnDelete();
            $table->string('speaker')->nullable()->after('material_id');
        });

        // The quotes behind a row, read-only (06 · 6.5).
        Schema::table('diagnosis_rows', function (Blueprint $table) {
            $table->json('quotes')->nullable()->after('reasoning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnosis_rows', function (Blueprint $table) {
            $table->dropColumn('quotes');
        });

        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_id');
            $table->dropColumn('speaker');
        });
    }
};
