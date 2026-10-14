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
        // Versions are never deleted: in a dispute it must be known which text applied (05 · 5.1).
        Schema::create('legal_document_versions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('file_path');
            $table->string('original_name');
            $table->date('effective_from');
            $table->boolean('requires_reacceptance')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'effective_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_document_versions');
    }
};
