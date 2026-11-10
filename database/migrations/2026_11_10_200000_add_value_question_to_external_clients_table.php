<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ügyfélmérés (35 · 35.3): the client answers one general question for the
     * values, and its values are measured from that answer. A value item keeps
     * no question of its own; a process item still does.
     */
    public function up(): void
    {
        Schema::table('external_clients', function (Blueprint $table) {
            $table->text('value_question')->nullable()->after('description');
        });

        Schema::table('external_client_items', function (Blueprint $table) {
            $table->text('question')->nullable()->change();
        });

        DB::table('external_client_items')->where('kind', 'value')->update(['question' => null]);
    }

    public function down(): void
    {
        DB::table('external_client_items')->whereNull('question')->update(['question' => '']);

        Schema::table('external_client_items', function (Blueprint $table) {
            $table->text('question')->nullable(false)->change();
        });

        Schema::table('external_clients', function (Blueprint $table) {
            $table->dropColumn('value_question');
        });
    }
};
