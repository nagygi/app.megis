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
        Schema::create('diagnosis_types', function (Blueprint $table) {
            $table->id();
            // Types the code refers to (e.g. Megélés: 08 · 8.3). The name stays editable.
            $table->string('key')->nullable()->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // The starting types (04 · 4.1) come with the schema, because the code relies on them.
        $now = now();
        DB::table('diagnosis_types')->insert(collect([
            'initial' => 'Kezdő diagnózis',
            'workshop' => 'Workshop',
            'mirror' => 'Értéktükör',
            'experience' => 'Megélés',
            'process' => 'Folyamat',
        ])->map(fn (string $name, string $key) => [
            'key' => $key,
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnosis_types');
    }
};
