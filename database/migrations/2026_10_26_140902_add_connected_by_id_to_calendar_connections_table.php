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
        Schema::table('calendar_connections', function (Blueprint $table) {
            // The admin who loaded the leader's file or link at the leader's request (09 · 9.2).
            $table->foreignId('connected_by_id')->nullable()->after('main_diagnosis_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_connections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('connected_by_id');
        });
    }
};
