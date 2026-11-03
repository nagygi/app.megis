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
        // When a member last opened or worked in the organization, shown on the admin list (02 · 2.2).
        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('deactivated_at');
        });

        // The existing organizations start from their members' last login: an approximation,
        // since a member of several organizations logged in to one of them only.
        DB::table('organizations')->update([
            'last_activity_at' => DB::table('organization_user')
                ->join('users', 'users.id', '=', 'organization_user.user_id')
                ->whereColumn('organization_user.organization_id', 'organizations.id')
                ->selectRaw('max(users.last_login_at)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('last_activity_at');
        });
    }
};
