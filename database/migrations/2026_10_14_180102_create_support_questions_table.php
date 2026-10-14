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
        // Questions shown during a recording after 5 seconds of silence (06 · 6.7).
        Schema::create('support_questions', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Starting examples; the final wording comes from mégis (06 · 6.7).
        $now = now();
        DB::table('support_questions')->insert(collect([
            'Ki volt még ott?',
            'Mit éreztél abban a pillanatban?',
            'Mit tettél végül?',
            'Miért volt ez fontos neked?',
        ])->map(fn (string $text, int $position) => [
            'text' => $text,
            'is_active' => true,
            'position' => $position,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_questions');
    }
};
