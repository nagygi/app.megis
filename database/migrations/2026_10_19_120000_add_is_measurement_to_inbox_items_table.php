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
        // The Értéktükör rows are listed under Értékmérések, apart from the Beérkező. The mark
        // stays on the row: an anonymous run and its recording are deleted after 24 hours (06 · 6.1).
        Schema::table('inbox_items', function (Blueprint $table) {
            $table->boolean('is_measurement')->default(false)->after('type');
        });

        DB::table('inbox_items')
            ->where('type', 'mirror_booking')
            ->orWhere('subject_type', 'mirror_session')
            ->orWhere(fn ($material) => $material
                ->where('subject_type', 'material')
                ->whereIn('subject_id', DB::table('materials')->select('id')->whereIn('context', ['public_mirror', 'mirror'])))
            // A failed anonymous run that is already deleted: only its summary names it.
            ->orWhere(fn ($pruned) => $pruned
                ->where('type', 'processing_error')
                ->where('subject_type', 'material')
                ->whereNotIn('subject_id', DB::table('materials')->select('id'))
                ->where('summary', 'like', '%Értéktükör'))
            ->update(['is_measurement' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbox_items', function (Blueprint $table) {
            $table->dropColumn('is_measurement');
        });
    }
};
