<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Names may be empty until the invited customer registers (02 · 2.5).
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            // Customers may log in with an e-mailed code only (14 · 14.3).
            $table->string('password')->nullable();
            $table->string('role')->nullable()->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            // Admin lockout after 10 failed logins, lifted from the console only (01 · 1.2).
            $table->timestamp('locked_at')->nullable();
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            // Customer password pause after 10 failed passwords (14 · 14.2).
            $table->unsignedSmallInteger('failed_password_attempts')->default(0);
            $table->timestamp('password_paused_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('last_organization_id')->nullable();
            // Raised on password change, reset or deactivation to end every other session.
            $table->unsignedInteger('auth_epoch')->default(0);
            $table->timestamp('mail_undeliverable_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
