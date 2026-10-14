<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The diagnosis flow of a product is chosen by its key (17 · 17.3); the name stays editable.
        // A product without a key has no diagnosis flow yet.
        Schema::table('products', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('id');
        });

        foreach ([
            'snapshot_external' => 'Snapshot · Külső',
            'snapshot_internal' => 'Snapshot · Belső',
            'full' => 'Teljes',
        ] as $key => $name) {
            DB::table('products')->where('name', $name)->whereNull('key')->update(['key' => $key]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn('key');
        });
    }
};
