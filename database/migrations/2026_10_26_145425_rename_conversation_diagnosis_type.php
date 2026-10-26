<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Spelling fix of the seeded name (19 · 19.5); a name the admin already changed stays.
        DB::table('diagnosis_types')
            ->where('key', 'conversation')
            ->where('name', 'Beszélgetés a mégisszel')
            ->update(['name' => 'Beszélgetés a mégissel']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('diagnosis_types')
            ->where('key', 'conversation')
            ->where('name', 'Beszélgetés a mégissel')
            ->update(['name' => 'Beszélgetés a mégisszel']);
    }
};
