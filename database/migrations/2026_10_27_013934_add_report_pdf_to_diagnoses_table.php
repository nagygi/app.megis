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
        // A PDF uploaded by the admin; "Letöltöm · PDF" gives it instead of the generated one (28 · 28.3).
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->string('report_pdf_path')->nullable()->after('speaker');
            $table->string('report_pdf_name')->nullable()->after('report_pdf_path');
            $table->timestamp('report_pdf_uploaded_at')->nullable()->after('report_pdf_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropColumn(['report_pdf_path', 'report_pdf_name', 'report_pdf_uploaded_at']);
        });
    }
};
