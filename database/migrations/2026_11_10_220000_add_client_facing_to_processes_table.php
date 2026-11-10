<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ügyfél folyamat (35 · 35.3): a process the organization's clients take
     * part in, asked in the Ügyfélmérés even before it is accepted.
     */
    public function up(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->boolean('client_facing')->default(false)->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn('client_facing');
        });
    }
};
