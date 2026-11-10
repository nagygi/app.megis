<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ügyfélmérés in a diagnosis (35 · 35.4–35.6): which clients take part, and
     * what their users answer.
     */
    public function up(): void
    {
        Schema::create('external_client_main_diagnosis', function (Blueprint $table) {
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_client_id')->constrained()->cascadeOnDelete();
            // When its users got the invitation: as the diagnosis started running, or later.
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->primary(['main_diagnosis_id', 'external_client_id']);
        });

        Schema::create('external_client_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // The item asked; its kind, value, process and question stay with the answer.
            $table->foreignId('external_client_item_id')->nullable()->constrained()->nullOnDelete();
            // value · process
            $table->string('kind');
            $table->uuid('value_key')->nullable();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['main_diagnosis_id', 'user_id', 'external_client_item_id'], 'external_client_answers_unique_item');
            $table->index(['main_diagnosis_id', 'external_client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_client_answers');
        Schema::dropIfExists('external_client_main_diagnosis');
    }
};
