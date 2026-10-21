<?php

namespace App\Livewire\Admin\Diagnoses;

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\CapabilityState;
use App\Enums\FindingStatus;
use App\Enums\PreconditionValue;
use App\Exceptions\DiagnosisException;
use App\Models\CapabilityAssessment;
use App\Models\CapabilityFinding;
use App\Models\CapabilityPreconditionAssessment;
use App\Models\CapabilityTrace;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\Precondition;
use App\Services\Capabilities\CapabilityAssessmentService;
use App\Services\Capabilities\CapabilityReviewService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The capabilities of a diagnosis (NM-12): per capability and level the
 * automatic and the final state and confidence, the findings with their
 * traces, E1–E9 and the candidates. The admin confirms or rejects findings,
 * leaves traces out, approves or writes the state ("magas" only by hand).
 */
class Capabilities extends Component
{
    public Diagnosis $diagnosis;

    public ?int $overriding = null;

    public string $overrideState = '';

    public string $overrideConfidence = '';

    public string $overrideReason = '';

    /** @var array<int, string> assessment id => precondition id */
    public array $blockers = [];

    /** @var array<int, string> precondition assessment id => value */
    public array $preconditionValues = [];

    /** @var array<int, string> precondition assessment id => note */
    public array $preconditionNotes = [];

    public function mount(Diagnosis $diagnosis): void
    {
        Gate::authorize('view', $diagnosis);

        $this->diagnosis = $diagnosis;
    }

    public function confirmFinding(int $findingId, CapabilityReviewService $review): void
    {
        $this->act(fn () => $review->confirmFinding($this->diagnosis, $this->finding($findingId), auth()->user()), 'finding_confirmed');
    }

    public function rejectFinding(int $findingId, CapabilityReviewService $review): void
    {
        $this->act(fn () => $review->rejectFinding($this->diagnosis, $this->finding($findingId), auth()->user()), 'finding_rejected');
    }

    public function confirmCapability(int $capabilityId, string $level, CapabilityReviewService $review): void
    {
        $this->act(fn () => $review->confirmCapability($this->diagnosis, $capabilityId, CapabilityLevel::from($level), auth()->user()), 'capability_confirmed');
    }

    public function toggleTrace(int $traceId, CapabilityReviewService $review): void
    {
        $trace = CapabilityTrace::query()->whereIn('material_id', $review->materialIds($this->diagnosis))->findOrFail($traceId);

        $this->act(fn () => $review->excludeTrace($this->diagnosis, $trace, auth()->user(), $trace->excluded_at === null), 'trace_toggled');
    }

    public function approve(int $assessmentId, CapabilityReviewService $review): void
    {
        $this->act(fn () => $review->approveAssessment($this->diagnosis, $this->assessment($assessmentId), auth()->user()), 'approved');
    }

    public function startOverride(int $assessmentId): void
    {
        $assessment = $this->assessment($assessmentId);

        $this->overriding = $assessment->id;
        $this->overrideState = $assessment->state->value;
        $this->overrideConfidence = $assessment->confidence?->value ?? '';
        $this->overrideReason = $assessment->override_reason ?? '';
    }

    public function saveOverride(CapabilityReviewService $review): void
    {
        $this->validate([
            'overrideState' => ['required', Rule::enum(CapabilityState::class)],
            'overrideConfidence' => ['nullable', Rule::enum(CapabilityConfidence::class)],
            'overrideReason' => ['required', 'string', 'max:2000'],
        ], attributes: [
            'overrideState' => __('capabilities.review.state'),
            'overrideConfidence' => __('capabilities.review.confidence'),
            'overrideReason' => __('capabilities.review.reason'),
        ]);

        $assessment = $this->assessment((int) $this->overriding);

        $this->act(fn () => $review->overrideAssessment(
            $this->diagnosis,
            $assessment,
            CapabilityState::from($this->overrideState),
            CapabilityConfidence::tryFrom($this->overrideConfidence),
            $this->overrideReason,
            auth()->user(),
        ), 'overridden');

        $this->reset(['overriding', 'overrideState', 'overrideConfidence', 'overrideReason']);
    }

    public function cancelOverride(): void
    {
        $this->reset(['overriding', 'overrideState', 'overrideConfidence', 'overrideReason']);
    }

    public function resetAssessment(int $assessmentId, CapabilityReviewService $review): void
    {
        $this->act(fn () => $review->resetAssessment($this->diagnosis, $this->assessment($assessmentId), auth()->user()), 'reset');
    }

    public function saveBlocker(int $assessmentId, CapabilityReviewService $review): void
    {
        $preconditionId = filled($this->blockers[$assessmentId] ?? null) ? (int) $this->blockers[$assessmentId] : null;
        abort_if($preconditionId !== null && ! Precondition::query()->whereKey($preconditionId)->exists(), 404);

        $this->act(fn () => $review->setBlocker($this->diagnosis, $this->assessment($assessmentId), $preconditionId, null, auth()->user()), 'blocker_saved');
    }

    public function savePrecondition(int $preconditionAssessmentId, CapabilityReviewService $review): void
    {
        $this->validate([
            'preconditionValues.'.$preconditionAssessmentId => ['required', Rule::enum(PreconditionValue::class)],
            'preconditionNotes.'.$preconditionAssessmentId => ['nullable', 'string', 'max:1000'],
        ]);

        $assessment = CapabilityPreconditionAssessment::query()->whereBelongsTo($this->diagnosis)->findOrFail($preconditionAssessmentId);

        $this->act(fn () => $review->setPrecondition(
            $this->diagnosis,
            $assessment,
            PreconditionValue::from($this->preconditionValues[$preconditionAssessmentId]),
            $this->preconditionNotes[$preconditionAssessmentId] ?? null,
            auth()->user(),
        ), 'precondition_saved');
    }

    public function rerun(int $materialId, CapabilityReviewService $review): void
    {
        $material = Material::query()->whereIn('id', $review->materialIds($this->diagnosis))->findOrFail($materialId);

        $this->act(fn () => $review->rerun($this->diagnosis, $material, auth()->user()), 'rerun');
    }

    public function render(CapabilityAssessmentService $assessments): View
    {
        $materials = $assessments->materialsFor($this->diagnosis);
        $findings = CapabilityFinding::query()
            ->whereIn('material_id', $materials->pluck('id'))
            ->with(['pattern', 'traces', 'reviewer'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (CapabilityFinding $finding) => $finding->capability_id.'|'.$finding->subject_level->value);
        $assessmentRows = $this->diagnosis->capabilityAssessments()
            ->with(['capability', 'blocker'])
            ->get()
            ->sortBy(fn (CapabilityAssessment $assessment) => [array_search($assessment->subject_level, CapabilityLevel::cases(), true), $assessment->capability->code]);
        $preconditions = $this->diagnosis->preconditionAssessments()->with('precondition')->get()->sortBy('precondition.code');

        foreach ($preconditions as $row) {
            $this->preconditionValues[$row->id] ??= $row->value->value;
            $this->preconditionNotes[$row->id] ??= (string) $row->note;
        }

        foreach ($assessmentRows as $row) {
            $this->blockers[$row->id] ??= (string) $row->blocker_precondition_id;
        }

        return view('livewire.admin.diagnoses.capabilities', [
            'main' => $this->diagnosis->main,
            'materials' => $materials,
            'groups' => $assessmentRows->groupBy(fn (CapabilityAssessment $assessment) => $assessment->subject_level->value),
            'findings' => $findings,
            'preconditionRows' => $preconditions,
            'preconditionOptions' => Precondition::query()->orderBy('code')->get(),
            'candidates' => CapabilityTrace::query()->whereIn('material_id', $materials->pluck('id'))->usable()->whereNotNull('candidate')->orderBy('id')->get(),
            'proposedCount' => $findings->flatten()->where('status', FindingStatus::Proposed)->count(),
        ])->layout('layouts::admin', ['organization' => $this->diagnosis->organization])
            ->title(__('capabilities.review.title').' · '.$this->diagnosis->name);
    }

    /**
     * @param  callable(): mixed  $action
     */
    private function act(callable $action, string $flash): void
    {
        Gate::authorize('update', $this->diagnosis);

        try {
            $action();
            session()->flash('status', __('capabilities.review.flash.'.$flash));
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    private function finding(int $findingId): CapabilityFinding
    {
        return CapabilityFinding::query()->whereIn('material_id', app(CapabilityReviewService::class)->materialIds($this->diagnosis))->findOrFail($findingId);
    }

    private function assessment(int $assessmentId): CapabilityAssessment
    {
        return CapabilityAssessment::query()->whereBelongsTo($this->diagnosis)->findOrFail($assessmentId);
    }
}
