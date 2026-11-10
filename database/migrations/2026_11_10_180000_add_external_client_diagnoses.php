<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ügyfélmérés (35 · 35.7): one automatic subdiagnosis per client of a main
     * diagnosis, of its own system type, so the team's rows stay as they are.
     */
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->foreignId('external_client_id')->nullable()->after('process_id')->constrained()->nullOnDelete();
        });

        // A type an admin already created with this name becomes the system type.
        DB::table('diagnosis_types')->updateOrInsert(
            ['name' => 'Ügyfélmérés'],
            ['key' => 'external_client', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function down(): void
    {
        // The clients' subdiagnoses go with their type.
        DB::table('diagnoses')->whereIn('diagnosis_type_id', DB::table('diagnosis_types')->where('key', 'external_client')->select('id'))->delete();
        DB::table('diagnosis_types')->where('key', 'external_client')->delete();

        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('external_client_id');
        });
    }
};
