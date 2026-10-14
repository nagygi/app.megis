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
        // A user's own values (24), shared by every diagnosis they take part in (25 · 25.7).
        // The working copy; leaving the step records a version (24, question 5).
        Schema::create('personal_value_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // The Értéktükör the list was taken from, to offer a newer one (24, question 4).
            $table->foreignId('imported_mirror_session_id')->nullable()->constrained('mirror_sessions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('personal_value_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_value_set_id')->constrained()->cascadeOnDelete();
            $table->uuid('key');
            // Always an Értéktár value: a new one goes there as "Felhasználó által felvett" (24, question 3).
            $table->foreignId('value_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_version_id')->nullable()->constrained('versions')->nullOnDelete();
            $table->string('name');
            $table->text('meaning');
            $table->text('non_meaning')->nullable();
            // Required only for a private person, whose own values are the organization's (24, question 2).
            $table->text('low_example')->nullable();
            $table->text('high_example')->nullable();
            $table->text('example')->nullable();
            // mirror · library · new (24 · 24.1)
            $table->string('source');
            // The Értéktükör quotes the value came with (24 · 24.5).
            $table->json('quotes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['personal_value_set_id', 'key']);
            $table->unique(['personal_value_set_id', 'value_id']);
        });

        // A value a user added stays theirs until it is reviewed (24, question 3; 03 · 3.7).
        Schema::table('values', function (Blueprint $table) {
            $table->foreignId('origin_user_id')->nullable()->after('origin_organization_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('values', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_user_id');
        });
        Schema::dropIfExists('personal_value_set_items');
        Schema::dropIfExists('personal_value_sets');
    }
};
