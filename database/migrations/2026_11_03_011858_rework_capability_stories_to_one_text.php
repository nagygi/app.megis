<?php

use App\Models\Diagnosis;
use App\Services\Capabilities\CapabilityAssessmentService;
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
        // The Képességek step asks one free text instead of value cards (NM-12). The cards
        // told so far are test data: they go with their materials (traces and findings
        // cascade), and the capability rows of their diagnoses are calculated again.
        $diagnosisIds = DB::table('materials')
            ->where('context', 'capability_story')
            ->whereNotNull('parent_diagnosis_id')
            ->distinct()
            ->pluck('parent_diagnosis_id');

        DB::table('capability_stories')->delete();
        DB::table('materials')->where('context', 'capability_story')->delete();

        Diagnosis::query()->whereKey($diagnosisIds)->each(fn (Diagnosis $diagnosis) => app(CapabilityAssessmentService::class)->refresh($diagnosis));

        // One text per user and main diagnosis.
        Schema::table('capability_stories', function (Blueprint $table) {
            $table->unique(['main_diagnosis_id', 'user_id']);
        });

        Schema::table('capability_stories', function (Blueprint $table) {
            $table->dropIndex(['main_diagnosis_id', 'user_id']);
            $table->dropColumn(['value_source', 'value_key', 'value_name', 'value_meaning', 'kind', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capability_stories', function (Blueprint $table) {
            $table->string('value_source')->nullable()->after('user_id');
            $table->uuid('value_key')->nullable()->after('value_source');
            $table->string('value_name')->nullable()->after('value_key');
            $table->text('value_meaning')->nullable()->after('value_name');
            $table->string('kind')->nullable()->after('value_meaning');
            $table->unsignedSmallInteger('position')->default(0)->after('material_id');
            $table->index(['main_diagnosis_id', 'user_id']);
        });

        Schema::table('capability_stories', function (Blueprint $table) {
            $table->dropUnique(['main_diagnosis_id', 'user_id']);
        });
    }
};
