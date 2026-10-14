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
        // Beérkező (22): one row per decision a person has to make, from every diagnosis.
        Schema::create('inbox_items', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('diagnosis_id')->nullable()->constrained()->nullOnDelete();
            // What the row is about, e.g. a material or an invitation.
            $table->nullableMorphs('subject');
            $table->string('summary', 500);
            $table->json('payload')->nullable();
            // The same open question is never listed twice; the key also closes it automatically.
            $table->string('dedupe_key')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('close_reason')->nullable();
            $table->timestamps();

            $table->index(['closed_at', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_items');
    }
};
