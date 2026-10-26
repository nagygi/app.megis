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
        Schema::table('mirror_sessions', function (Blueprint $table) {
            // The e-mail comes on ET2 now; the link went out (a new address) or not (an existing account).
            $table->timestamp('confirmation_sent_at')->nullable()->after('accepted_at');
            // Where the ET4 suggestions of the mégis Coach stand (NM-06).
            $table->string('insight_status')->nullable()->after('status');
        });

        // A mirror that asked for its account on ET5 before this: its link went out then.
        DB::table('mirror_sessions')->where('status', 'awaiting_confirmation')->update(['confirmation_sent_at' => DB::raw('accepted_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mirror_sessions', function (Blueprint $table) {
            $table->dropColumn(['confirmation_sent_at', 'insight_status']);
        });
    }
};
