<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ügyfélmérés (35 · 35.3): "Bármilyen értéket mérünk" and chosen values
     * exclude each other. A client saved with both keeps the switch.
     */
    public function up(): void
    {
        DB::table('external_client_items')
            ->where('kind', 'value')
            ->whereIn('external_client_id', DB::table('external_clients')->where('measures_any_value', true)->select('id'))
            ->delete();
    }

    /**
     * The chosen values are not brought back.
     */
    public function down(): void {}
};
