<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ügyfélmérés (35): the organization's own clients, their users and what
     * we ask them. Optional per organization (35 · 35.1).
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('external_clients_enabled')->default(false)->after('headcount_limit');
        });

        Schema::create('external_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        // The client's users: accounts of their own, not members of the organization (35 · 35.2).
        Schema::create('external_client_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['external_client_id', 'user_id']);
        });

        // What we measure at the client, each with its own question (35 · 35.3).
        Schema::create('external_client_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_client_id')->constrained()->cascadeOnDelete();
            // value · process
            $table->string('kind');
            // The stable key of the organization's value set item.
            $table->uuid('value_key')->nullable();
            $table->foreignId('process_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_client_items');
        Schema::dropIfExists('external_client_user');
        Schema::dropIfExists('external_clients');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('external_clients_enabled');
        });
    }
};
