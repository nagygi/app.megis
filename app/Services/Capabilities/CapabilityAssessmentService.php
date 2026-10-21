<?php

namespace App\Services\Capabilities;

use App\Enums\CapabilityLevel;
use App\Enums\CapabilityState;
use App\Enums\FindingStatus;
use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\PreconditionValue;
use App\Enums\TraceDirection;
use App\Models\Capability;
use App\Models\CapabilityAssessment;
use App\Models\CapabilityFinding;
use App\Models\CapabilityPreconditionAssessment;
use App\Models\CapabilityTrace;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\Precondition;
use App\Services\Capabilities\Measurement\CapabilityStateCalculator;
use App\Services\Capabilities\Measurement\FindingInput;
use App\Services\Capabilities\Measurement\PreconditionCalculator;
use App\Services\Capabilities\Measurement\SubjectContext;
use App\Services\Capabilities\Measurement\TraceInput;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\Inbox\InboxService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The capabilities and E1–E9 of a diagnosis from the findings of its
 * materials (NM-12): per capability and level the automatic state and
 * confidence; a value an admin set stays, an approved row whose automatic
 * value changed waits for a new approval. Published diagnoses do not change.
 */
class CapabilityAssessmentService
{
    public function __construct(
        private CapabilityStateCalculator $states,
        private PreconditionCalculator $preconditions,
        private InboxService $inbox,
    ) {}

    /**
     * Every diagnosis the material counts in, then the Beérkező rows of the material.
     */
    public function refreshForMaterial(Material $material): void
    {
        $this->refreshMaterials(collect([$material]));
    }

    /**
     * Each diagnosis the materials count in, once.
     *
     * @param  Collection<int, Material>  $materials
     */
    public function refreshMaterials(Collection $materials): void
    {
        $materials->flatMap(fn (Material $material) => $this->diagnosesOf($material))
            ->unique('id')
            ->each(fn (Diagnosis $diagnosis) => $this->refresh($diagnosis));

        $materials->each(fn (Material $material) => $this->syncCandidates($material));
    }

    /**
     * The main diagnosis the material is attached to, and the diagnoses built from it.
     *
     * @return Collection<int, Diagnosis>
     */
    public function diagnosesOf(Material $material): Collection
    {
        return Diagnosis::query()
            ->where('material_id', $material->id)
            ->when($material->parent_diagnosis_id !== null, fn ($query) => $query->orWhere('id', $material->parent_diagnosis_id))
            ->get();
    }

    public function refresh(Diagnosis $diagnosis): void
    {
        $main = $diagnosis->main;

        if ($main !== null && in_array($main->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true)) {
            return;
        }

        $materials = $this->materialsFor($diagnosis);

        if ($materials->isEmpty()) {
            return;
        }

        $findings = CapabilityFinding::query()
            ->whereIn('material_id', $materials->pluck('id'))
            ->with(['traces', 'pattern', 'capability.latestVersion'])
            ->get();
        $traces = CapabilityTrace::query()->whereIn('material_id', $materials->pluck('id'))->usable()->get();

        // Nothing measured and nothing written yet: no empty rows (the measurement may be off).
        if ($findings->isEmpty() && $traces->isEmpty() && ! $diagnosis->preconditionAssessments()->exists() && ! $diagnosis->capabilityAssessments()->exists()) {
            return;
        }

        // A diagnosis of one speaker of an admin upload reads that speaker only (06 · 6.3).
        if ($diagnosis->speaker !== null && $main === null) {
            $traces = $traces->where('speaker', $diagnosis->speaker)->values();
        }

        $usableIds = $traces->reject(fn (CapabilityTrace $trace) => $trace->isBlocked())->pluck('id')->all();

        DB::transaction(function () use ($diagnosis, $materials, $findings, $traces, $usableIds): void {
            $this->writeAssessments($diagnosis, $materials, $findings, $usableIds);
            $this->writePreconditions($diagnosis, $traces->filter(fn (CapabilityTrace $trace) => in_array($trace->id, $usableIds, true)));
        });

        $this->syncReview($diagnosis, $findings);
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, CapabilityFinding>  $findings
     * @param  list<int>  $usableIds
     */
    private function writeAssessments(Diagnosis $diagnosis, Collection $materials, Collection $findings, array $usableIds): void
    {
        $byMaterial = $materials->keyBy('id');
        // No member yet is an unknown size, not a micro organization (KO-06).
        $size = $diagnosis->organization?->headcount();
        $context = fn (Capability $capability, CapabilityLevel $level) => new SubjectContext(
            level: $level,
            tempting: (bool) $capability->tempting,
            organizationSize: $size > 0 ? $size : null,
            sourcesExisted: true,
            auditSensitive: in_array($capability->code, (array) config('megis.capabilities.audit_sensitive'), true),
        );

        $groups = $findings->groupBy(fn (CapabilityFinding $finding) => $finding->capability_id.'|'.$finding->subject_level->value);
        $kept = [];

        foreach ($groups as $group) {
            $first = $group->first();
            $inputs = $group
                ->map(fn (CapabilityFinding $finding) => new FindingInput(
                    kind: $finding->kind,
                    subtype: $finding->pattern?->subtype,
                    status: $finding->status,
                    traces: $finding->traces
                        ->filter(fn (CapabilityTrace $trace) => in_array($trace->id, $usableIds, true))
                        ->map(fn (CapabilityTrace $trace) => $this->traceInput($trace, $byMaterial->get($trace->material_id)))
                        ->values()
                        ->all(),
                    needsFirstApproval: $finding->needs_first_approval,
                ))
                ->filter(fn (FindingInput $input) => $input->traces !== [])
                ->values()
                ->all();

            // Every trace of the group is excluded or belongs to another speaker.
            if ($inputs === [] && $group->every(fn (CapabilityFinding $finding) => $finding->status !== FindingStatus::Rejected)) {
                continue;
            }

            $result = $this->states->calculate($inputs, $context($first->capability, $first->subject_level));
            $assessment = CapabilityAssessment::query()->firstOrNew([
                'diagnosis_id' => $diagnosis->id,
                'capability_id' => $first->capability_id,
                'subject_level' => $first->subject_level,
            ]);

            $changed = $assessment->exists && ($assessment->automatic_state !== $result->state || $assessment->automatic_confidence !== $result->confidence);

            $assessment->fill([
                'capability_version_id' => $first->capability->latestVersion?->id,
                'automatic_state' => $result->state,
                'automatic_confidence' => $result->confidence,
                'automatic_basis' => $result->toArray(),
            ]);

            if (! $assessment->isOverridden()) {
                $assessment->fill(['state' => $result->state, 'confidence' => $result->confidence]);
            }

            // An approved row whose automatic value changed waits for a new approval.
            if ($changed && $assessment->isApproved() && ! $assessment->isOverridden()) {
                $assessment->fill(['approved_at' => null, 'approved_by' => null]);
            }

            $assessment->save();
            $kept[] = $assessment->id;
        }

        // Rows without findings any more go, unless an admin wrote or added them.
        $diagnosis->capabilityAssessments()
            ->whereNotIn('id', $kept)
            ->whereNull('created_by')
            ->whereNull('overridden_at')
            ->delete();

        $this->markUneven($diagnosis);
    }

    /**
     * Egyenetlenség (6.8): present at one level and absent at another.
     */
    private function markUneven(Diagnosis $diagnosis): void
    {
        $assessments = $diagnosis->capabilityAssessments()->get()->groupBy('capability_id');

        foreach ($assessments as $rows) {
            $states = $rows->pluck('state');
            $uneven = $states->contains(CapabilityState::Present) && $states->contains(CapabilityState::Absent);

            foreach ($rows as $row) {
                if ($row->uneven !== $uneven) {
                    $row->forceFill(['uneven' => $uneven])->save();
                }
            }
        }
    }

    /**
     * @param  Collection<int, CapabilityTrace>  $traces
     */
    private function writePreconditions(Diagnosis $diagnosis, Collection $traces): void
    {
        $directions = [];

        foreach ($traces as $trace) {
            foreach ($trace->preconditions as $precondition) {
                $directions[$precondition['code']][] = TraceDirection::tryFrom($precondition['direction']) ?? TraceDirection::Mixed;
            }
        }

        foreach (Precondition::query()->orderBy('code')->get() as $precondition) {
            $value = $this->preconditions->value($directions[$precondition->code] ?? []);
            $assessment = CapabilityPreconditionAssessment::query()->firstOrNew(['diagnosis_id' => $diagnosis->id, 'precondition_id' => $precondition->id]);
            $changed = $assessment->exists && $assessment->automatic_value !== $value;

            $assessment->automatic_value = $value;

            if (! $assessment->isOverridden()) {
                $assessment->value = $value;
            }

            if ($changed && ! $assessment->isOverridden()) {
                $assessment->approved_at = null;
                $assessment->approved_by = null;
            }

            $assessment->save();
        }
    }

    private function traceInput(CapabilityTrace $trace, ?Material $material): TraceInput
    {
        return new TraceInput(
            id: $trace->id,
            personKey: $this->personKey($trace, $material),
            method: $material === null ? 'unknown' : $this->method($material),
            occasionKey: $material?->process_occasion_id !== null ? 'occasion:'.$material->process_occasion_id : 'material:'.$trace->material_id,
            isRaw: $trace->is_raw,
            directness: $trace->directness,
            direction: $trace->direction,
            flags: $trace->flags,
            externalVoice: $trace->speaker_role === 'external',
        );
    }

    /**
     * Who said it: the user of a one-person material, the user an admin assigned
     * to the speaker, or the speaker label within its material.
     */
    private function personKey(CapabilityTrace $trace, ?Material $material): string
    {
        if ($material !== null && $material->context !== MaterialContext::AdminUpload && $material->user_id !== null) {
            return 'user:'.$material->user_id;
        }

        $assigned = $material?->speaker_assignments[(string) $trace->speaker] ?? null;

        return $assigned !== null ? 'user:'.$assigned : 'speaker:'.$trace->material_id.':'.($trace->speaker ?? '?');
    }

    private function method(Material $material): string
    {
        return match (true) {
            $material->context->isMirror() => 'mirror',
            $material->is_situation => 'note',
            $material->context === MaterialContext::AdminUpload => 'conversation',
            $material->source->hasAudio() => 'recording',
            default => 'document',
        };
    }

    /**
     * The materials a diagnosis reads: those of a main diagnosis without the
     * withdrawn peers (18 · 18.4, 25 · 25.6), or the material of a plain one.
     *
     * @return Collection<int, Material>
     */
    public function materialsFor(Diagnosis $diagnosis): Collection
    {
        if ($diagnosis->main !== null) {
            return app(DiagnosisCalculator::class)->materials($diagnosis->main);
        }

        return $diagnosis->material_id === null ? collect() : Material::query()->whereKey($diagnosis->material_id)->get();
    }

    /**
     * "Képességek átnézése": open while the diagnosis has proposed findings.
     *
     * @param  Collection<int, CapabilityFinding>  $findings
     */
    private function syncReview(Diagnosis $diagnosis, Collection $findings): void
    {
        $key = 'capability-review:'.$diagnosis->id;
        $proposed = $findings->where('status', FindingStatus::Proposed)->count();

        if ($proposed === 0) {
            $this->inbox->closeAutomatically($key);

            return;
        }

        $this->inbox->open(
            InboxItemType::CapabilityReview,
            __('capabilities.inbox.review', ['diagnosis' => $diagnosis->name]),
            diagnosis: $diagnosis,
            subject: $diagnosis,
            dedupeKey: $key,
        );
    }

    /**
     * Traces no pattern or capability fits go to the mégis: only they add patterns (NY-26).
     */
    private function syncCandidates(Material $material): void
    {
        $key = 'capability-candidate:'.$material->id;
        $count = $material->capabilityTraces()->usable()->whereNotNull('candidate')->count();

        if ($count === 0) {
            $this->inbox->closeAutomatically($key);

            return;
        }

        $this->inbox->open(
            InboxItemType::CapabilityCandidate,
            __('capabilities.inbox.candidates', ['count' => $count, 'name' => $material->original_name ?? $material->context->label()]),
            organization: $material->organization,
            subject: $material,
            dedupeKey: $key,
        );
    }

    /**
     * The value E1–E9 has in a diagnosis, for the readiness check of the Bridge (NM-15).
     */
    public function preconditionValue(Diagnosis $diagnosis, string $code): PreconditionValue
    {
        return $diagnosis->preconditionAssessments()
            ->whereHas('precondition', fn ($query) => $query->where('code', $code))
            ->first()?->value ?? PreconditionValue::Unknown;
    }
}
