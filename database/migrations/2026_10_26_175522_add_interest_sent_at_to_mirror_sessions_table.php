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
        Schema::table('mirror_sessions', function (Blueprint $table) {
            // "Elküldöm" on ET5 goes once a mirror (06 · 6.1).
            $table->timestamp('interest_sent_at')->nullable()->after('curiosity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mirror_sessions', function (Blueprint $table) {
            $table->dropColumn('interest_sent_at');
        });
    }
};
