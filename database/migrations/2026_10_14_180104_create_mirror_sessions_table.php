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
        // One run of the Értéktükör (06 · 6.1, 6.2). Without a confirmed account
        // it lives 24 hours (expires_at); claimed, it belongs to the user.
        Schema::create('mirror_sessions', function (Blueprint $table) {
            $table->id();
            // The browser's key to its own mirror; never shown in a list.
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('diagnosis_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->string('email')->nullable();
            // The legal document versions accepted on ET5, with the time (06 · 6.1).
            $table->json('accepted_legal_versions')->nullable();
            $table->timestamp('accepted_at')->nullable();
            // The ticked curiosity questions; the admin sees them on the datasheet (33).
            $table->json('curiosity')->nullable();
            $table->timestamp('booking_requested_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mirror_sessions');
    }
};
