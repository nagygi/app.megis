<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foreign keys the app looks up or cascades on. PostgreSQL does not index a
     * foreign key by itself (NFR · 6). The audit columns (created_by, updated_by,
     * approved_by …) and the ai_call links are left out: nothing filters on them,
     * and users are never deleted, only anonymized.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'ai_calls' => ['organization_id'],
        'anonymous_value_stats' => ['value_id'],
        'audit_events' => ['actor_id', 'impersonation_id'],
        'calendar_connections' => ['main_diagnosis_id', 'organization_id', 'user_id'],
        'capabilities' => ['capability_group_id'],
        'capability_dependencies' => ['depends_on_id'],
        'capability_precondition' => ['precondition_id'],
        'consents' => ['main_diagnosis_id'],
        'detection_matches' => ['value_id', 'value_version_id'],
        'diagnoses' => ['diagnosis_type_id', 'material_id', 'process_id', 'team_id', 'value_set_version_id'],
        'diagnosis_claims' => ['diagnosis_id', 'diagnosis_row_id'],
        'diagnosis_rows' => ['area_id', 'process_id', 'value_id', 'value_version_id'],
        'diagnosis_transitions' => ['diagnosis_id'],
        'evidence_traces' => ['diagnosis_row_id', 'material_id', 'process_occasion_id', 'raw_detection_id', 'user_id'],
        'impersonations' => ['admin_id'],
        'inbox_items' => ['diagnosis_id', 'organization_id'],
        'legal_acceptances' => ['legal_document_version_id'],
        'main_diagnoses' => ['biggest_gap_row_id', 'product_id', 'value_set_version_id'],
        'main_diagnosis_participants' => ['personal_value_set_version_id'],
        'main_diagnosis_processes' => ['process_id'],
        'main_diagnosis_step_marks' => ['user_id'],
        'materials' => ['diagnosis_type_id', 'organization_id', 'parent_diagnosis_id', 'process_occasion_id'],
        'material_value_scores' => ['value_id', 'value_version_id'],
        'megis_conversations' => ['admin_id', 'main_diagnosis_id', 'with_user_id'],
        'mirror_sessions' => ['diagnosis_id', 'material_id', 'organization_id', 'user_id'],
        'occasion_experiences' => ['main_diagnosis_id', 'material_id', 'user_id', 'value_set_version_id'],
        'organizations' => ['contact_admin_id', 'product_id'],
        'organization_value_set_items' => ['source_version_id', 'value_id'],
        'personal_value_set_items' => ['source_version_id', 'value_id'],
        'personal_value_sets' => ['imported_mirror_session_id'],
        'process_conversations' => ['main_diagnosis_id', 'organization_id', 'user_id'],
        'processes' => ['merge_candidate_id', 'parent_id', 'responsible_user_id'],
        'process_metrics' => ['process_id'],
        'process_metric_values' => ['process_metric_id'],
        'process_participants' => ['process_id', 'user_id'],
        'process_renames' => ['process_id'],
        'process_team' => ['team_id'],
        'raw_detections' => ['material_id'],
        'reminder_dispatches' => ['user_id'],
        'report_feedback' => ['material_id', 'user_id'],
        'users' => ['last_organization_id'],
        'values' => ['origin_organization_id', 'origin_user_id', 'value_category_id'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $missing = array_values(array_filter($columns, fn (string $column): bool => ! Schema::hasIndex($table, [$column])));

            if ($missing === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($missing) {
                foreach ($missing as $column) {
                    $blueprint->index($column);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasIndex($table, $table.'_'.$column.'_index')) {
                        $blueprint->dropIndex([$column]);
                    }
                }
            });
        }
    }
};
