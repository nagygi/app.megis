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
        // The global Értéktár (03). The lists keep their order: the first item matters most (03 · 3.2).
        Schema::create('values', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->foreignId('value_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('meanings');
            $table->json('non_meanings');
            $table->json('high_examples');
            $table->json('low_examples');
            $table->json('examples');
            $table->json('keywords');
            // Both scale ends (1 and 6) are filled; only such a value can be chosen by a user (03 · 3.7).
            $table->boolean('has_endpoints')->default(false);
            // Lower-cased name and keywords, so search works the same on every database.
            $table->text('search_text');
            $table->string('origin');
            // Where a value from outside the admin form was created; it is selectable only
            // there until reviewed (03 · 3.7).
            $table->foreignId('origin_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('values');
    }
};
