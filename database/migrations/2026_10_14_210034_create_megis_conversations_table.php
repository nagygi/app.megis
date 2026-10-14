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
        // The mégis' conversations with the client, recorded by the admin (19 · 19.5).
        Schema::create('megis_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->dateTime('held_at');
            // leader · peer · external
            $table->string('with_kind');
            $table->foreignId('with_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('with_name')->nullable();
            // The admin leading it.
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // A reminder to one person; at most one per 48 hours (19 · 19.4, 26).
        Schema::create('reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // leader · peer · threshold · window
            $table->string('kind');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['main_diagnosis_id', 'user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_dispatches');
        Schema::dropIfExists('megis_conversations');
    }
};
