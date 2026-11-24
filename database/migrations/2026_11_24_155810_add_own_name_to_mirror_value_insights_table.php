<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ET4 (06 · 6.1): the user may rename a value of the mirror and write every
     * part of it, and approves each one, with or without the Coach's words.
     */
    public function up(): void
    {
        Schema::table('mirror_value_insights', function (Blueprint $table) {
            // The user's own name for the value; null keeps the detected one.
            $table->string('name')->nullable()->after('group_key');
            $table->text('meaning')->nullable()->change();
            $table->json('signs')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mirror_value_insights', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
