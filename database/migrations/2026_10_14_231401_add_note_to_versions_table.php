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
        // What changed and why (07 · 7.3): optional on save, automatic on a load.
        Schema::table('versions', function (Blueprint $table) {
            $table->string('note', 500)->nullable()->after('changed_fields');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('versions', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
