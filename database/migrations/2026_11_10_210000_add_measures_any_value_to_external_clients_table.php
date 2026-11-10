<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ügyfélmérés (35 · 35.3): besides the chosen values, any value found in
     * the client's answers is measured too.
     */
    public function up(): void
    {
        Schema::table('external_clients', function (Blueprint $table) {
            $table->boolean('measures_any_value')->default(false)->after('value_question');
        });
    }

    public function down(): void
    {
        Schema::table('external_clients', function (Blueprint $table) {
            $table->dropColumn('measures_any_value');
        });
    }
};
