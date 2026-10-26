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
        // ET4 · one value of a mirror in the user's own words, as the mégis Coach
        // suggested it from the quotes (NM-06). It goes with the mirror.
        Schema::create('mirror_value_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mirror_session_id')->constrained()->cascadeOnDelete();
            // The value group of ET3: "value:{id}" or "free:{name}".
            $table->string('group_key');
            // "Mit jelent nálad": the suggestion, or what the user wrote instead.
            $table->text('meaning');
            // "Miben látszik": a few short signs.
            $table->json('signs');
            // "Mi az 1, mi a 6".
            $table->text('low_example')->nullable();
            $table->text('high_example')->nullable();
            // "Ez az" or "Átírom".
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->foreignId('ai_call_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['mirror_session_id', 'group_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mirror_value_insights');
    }
};
