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
        // Content versions of versioned entities: values (03 · 3.3), value sets (08 · 8.4),
        // later capabilities (07) and personal values (24). A version never changes;
        // what uses an entity records the version it used.
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->morphs('versionable');
            $table->unsignedInteger('number');
            $table->jsonb('snapshot');
            $table->json('changed_fields');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->unique(['versionable_type', 'versionable_id', 'number']);
        });

        // Archiving, restoring and reviewing show in the version history without
        // creating a version (03 · 3.3).
        Schema::create('version_events', function (Blueprint $table) {
            $table->id();
            $table->morphs('versionable');
            $table->string('type');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_events');
        Schema::dropIfExists('versions');
    }
};
