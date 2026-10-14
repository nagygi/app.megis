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
        // The organization's values and the brand base together: the value set (08 · 8.4).
        // This is the working copy; every saved change is a version.
        Schema::create('organization_value_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            // The brand base (08 · 8.2); its root is the list of values itself.
            $table->text('purpose')->nullable();
            $table->text('value_proposition')->nullable();
            $table->text('impact')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3–5 values in the organization's own words (08 · 8.1).
        Schema::create('organization_value_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_value_set_id')->constrained()->cascadeOnDelete();
            // Stays the same across versions, so a diagnosis row can follow the value.
            $table->uuid('key');
            $table->foreignId('value_id')->constrained()->restrictOnDelete();
            // The Értéktár version the definition was taken from (08 · 8.1).
            $table->foreignId('source_version_id')->nullable()->constrained('versions')->nullOnDelete();
            $table->string('name');
            $table->text('meaning')->nullable();
            $table->text('non_meaning')->nullable();
            $table->text('low_example');
            $table->text('high_example');
            $table->text('example')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['organization_value_set_id', 'key']);
            $table->unique(['organization_value_set_id', 'value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_value_set_items');
        Schema::dropIfExists('organization_value_sets');
    }
};
