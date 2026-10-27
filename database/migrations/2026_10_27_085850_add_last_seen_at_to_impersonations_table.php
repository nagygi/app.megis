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
        // The last request in the customer's name; an abandoned session is closed from it (33 · 33.6).
        Schema::table('impersonations', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('impersonations', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
    }
};
