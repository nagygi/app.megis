<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Képességek (NM-12): the situations a leader or a peer tells to a value,
     * typed or spoken. Each sent one is a material the capability measurement reads.
     */
    public function up(): void
    {
        Schema::create('capability_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // own · organization: whose value the situation is told to
            $table->string('value_source');
            // The stable key of the value set item, and its words when asked.
            $table->uuid('value_key')->nullable();
            $table->string('value_name');
            $table->text('value_meaning')->nullable();
            // tested · missed · own: which question was asked
            $table->string('kind');
            $table->text('question');
            $table->text('answer')->nullable();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['main_diagnosis_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capability_stories');
    }
};
