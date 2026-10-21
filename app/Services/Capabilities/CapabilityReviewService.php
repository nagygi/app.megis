<?php

namespace App\Services\Capabilities;

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\CapabilityState;
use App\Enums\FindingStatus;
use App\Enums\MainDiagnosisStage;
use App\Enums\PreconditionValue;
use App\Exceptions\DiagnosisException;
use App\Jobs\DetectCapabilities;
use App\Models\CapabilityAssessment;
use App\Models\CapabilityFinding;
use App\Models\CapabilityPreconditionAssessment;
use App\Models\CapabilityTrace;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\User;
use App\Services\Diagnoses\ReadingService;

/**
 * What an admin decides about the capabilities of a diagnosis (NM-12): a
 * finding confirmed or rejected (a rejected proposal is a false alarm,
 * NY-27), a trace left out, the state and the confidence approved or written
 * by hand ("magas" only here, KO-01), a blocker, E1–E9. Every step is logged.
 */
class CapabilityReviewService
{
    public function __construct(
        private CapabilityAssessmentService $assessments,
        private PatternStatistics $statistics,
        private ReadingService $reading,
    ) {}

    public function confirmFinding(Diagnosis $diagnosis, CapabilityFinding $finding, User $actor): void
    {
        $this->decide($diagnosis, $finding, FindingStatus::Confirmed, $actor);
    }

    public function rejectFinding(Diagnosis $diagnosis, CapabilityFinding $finding, User $actor, ?string $note = null): void
    {
        $this->decide($diagnosis, $finding, FindingStatus::Rejected, $actor, $note);
    }

    /**
     * Every proposed finding of a capability at a level, except those a
     * person approves one by one (NY-26).
     */
    public function confirmCapability(Diagnosis $diagnosis, int $capabilityId, CapabilityLevel $level, User $actor): int
    {
        $this->ensureOpen($diagnosis);

        $findings = CapabilityFinding::query()
            ->whereIn('material_id', $this->materialIds($diagnosis))
            ->where('capability_id', $capabilityId)
            ->where('subject_level', $level)
            ->where('status', FindingStatus::Proposed)
            ->where('needs_first_approval', false)
            ->get();

        foreach ($findings as $finding) {
            $this->decide($diagnosis, $finding, FindingStatus::Confirmed, $actor, refresh: false);
        }

        $this->refresh($diagnosis);

        return $findings->count();
    }

    public function excludeTrace(Diagnosis $diagnosis, CapabilityTrace $trace, User $actor, bool $exclude = true): void
    {
        $this->ensureOpen($diagnosis);

        $trace->forceFill(['excluded_at' => $exclude ? now() : null, 'excluded_by' => $exclude ? $actor->id : null])->save();
        $this->reading->log($diagnosis, $trace, $exclude ? 'capability_trace_excluded' : 'capability_trace_included', $actor);
        $this->refresh($diagnosis);
    }

    public function approveAssessment(Diagnosis $diagnosis, CapabilityAssessment $assessment, User $actor): void
    {
        $this->ensureOpen($diagnosis);

        $assessment->forceFill(['approved_at' => now(), 'approved_by' => $actor->id])->save();
        $this->reading->log($diagnosis, $assessment, 'capability_approved', $actor);
    }

    /**
     * The final state and confidence by hand, with the reason; "magas" can only be set here (KO-01).
     */
    public function overrideAssessment(Diagnosis $diagnosis, CapabilityAssessment $assessment, CapabilityState $state, ?CapabilityConfidence $confidence, string $reason, User $actor): void
    {
        $this->ensureOpen($diagnosis);

        $assessment->forceFill([
            'state' => $state,
            'confidence' => $confidence,
            'override_reason' => $reason,
            'overridden_by' => $actor->id,
            'overridden_at' => now(),
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ])->save();

        $this->reading->log($diagnosis, $assessment, 'capability_overridden', $actor, [
            'state' => $state->value,
            'confidence' => $confidence?->value,
            'reason' => $reason,
        ]);
    }

    public function resetAssessment(Diagnosis $diagnosis, CapabilityAssessment $assessment, User $actor): void
    {
        $this->ensureOpen($diagnosis);

        $assessment->forceFill([
            'state' => $assessment->automatic_state,
            'confidence' => $assessment->automatic_confidence,
            'override_reason' => null,
            'overridden_by' => null,
            'overridden_at' => null,
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        $this->reading->log($diagnosis, $assessment, 'capability_reset', $actor);
    }

    /**
     * Akadály (6.7): the capability is blocked by a missing precondition, not by the team.
     */
    public function setBlocker(Diagnosis $diagnosis, CapabilityAssessment $assessment, ?int $preconditionId, ?string $note, User $actor): void
    {
        $this->ensureOpen($diagnosis);

        $assessment->forceFill(['blocker_precondition_id' => $preconditionId, 'blocker_note' => $preconditionId === null ? null : $note])->save();
        $this->reading->log($diagnosis, $assessment, 'capability_blocker', $actor, ['precondition_id' => $preconditionId, 'note' => $note]);
    }

    public function setPrecondition(Diagnosis $diagnosis, CapabilityPreconditionAssessment $assessment, PreconditionValue $value, ?string $note, User $actor): void
    {
        $this->ensureOpen($diagnosis);

        $overridden = $value !== $assessment->automatic_value || filled($note);

        $assessment->forceFill([
            'value' => $value,
            'note' => $note,
            'overridden_by' => $overridden ? $actor->id : null,
            'overridden_at' => $overridden ? now() : null,
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ])->save();

        $this->reading->log($diagnosis, $assessment, 'precondition_set', $actor, ['value' => $value->value, 'note' => $note]);
    }

    /**
     * Runs the capability measurement of a material again; what an admin decided stays.
     */
    public function rerun(Diagnosis $diagnosis, Material $material, User $actor): void
    {
        $this->ensureOpen($diagnosis);

        DetectCapabilities::dispatch($material, force: true);
        $this->reading->log($diagnosis, $material, 'capability_rerun', $actor);
    }

    /**
     * @return list<int>
     */
    public function materialIds(Diagnosis $diagnosis): array
    {
        return $this->assessments->materialsFor($diagnosis)->pluck('id')->all();
    }

    private function decide(Diagnosis $diagnosis, CapabilityFinding $finding, FindingStatus $status, User $actor, ?string $note = null, bool $refresh = true): void
    {
        $this->ensureOpen($diagnosis);

        // A decision is final: the false alarms of a pattern count it once (NY-27).
        if ($finding->status !== FindingStatus::Proposed) {
            return;
        }

        $finding->forceFill([
            'status' => $status,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $this->statistics->record($finding, $status);
        $this->reading->log($diagnosis, $finding, $status === FindingStatus::Confirmed ? 'capability_finding_confirmed' : 'capability_finding_rejected', $actor, array_filter(['note' => $note]));

        if ($refresh) {
            $this->refresh($diagnosis);
        }
    }

    /**
     * A decision counts in every diagnosis its material is part of.
     */
    private function refresh(Diagnosis $diagnosis): void
    {
        $this->assessments->refreshMaterials($this->assessments->materialsFor($diagnosis));
    }

    /**
     * After publishing a main diagnosis its capabilities do not change.
     */
    private function ensureOpen(Diagnosis $diagnosis): void
    {
        $main = $diagnosis->main;

        if ($main !== null && in_array($main->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true)) {
            throw new DiagnosisException(__('capabilities.review.closed'));
        }
    }
}
