<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Loads of the Értéktár from a table (03 · 3.6), the file itself is not kept.
        Schema::create('value_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->unsignedInteger('created_count');
            $table->unsignedInteger('updated_count');
            $table->unsignedInteger('unchanged_count');
            $table->unsignedInteger('categories_created_count');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('value_imports');
    }
};
