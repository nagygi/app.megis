<?php

namespace App\Livewire\Admin\Diagnoses;

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisStatus;
use App\Enums\ExperienceState;
use App\Enums\ScoreState;
use App\Exceptions\DiagnosisException;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\Value;
use App\Services\Diagnoses\DiagnosisAggregator;
use App\Services\Diagnoses\DiagnosisService;
use App\Services\Diagnoses\ProcessOccasionBreakdown;
use App\Services\Materials\MaterialStore;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * A diagnosis with its two blocks, values and the brand base (04 · 4.4),
 * the status steps with their log (4.3) and the aggregated data (4.5). A
 * calculated one (13, 18) is written over with a reason, next to the
 * automatic value; a process diagnosis lists its occasions (13 · 13.5).
 */
class Show extends Component
{
    /**
     * A score or experience: 1–6 with at most one decimal (aggregated rows).
     */
    private const NUMBER_PATTERN = '/^(?:[1-5](?:\.\d)?|6(?:\.0)?)$/';

    public Diagnosis $diagnosis;

    public string $newValueName = '';

    public ?int $editingRowId = null;

    public string $rowScore = '';

    public string $rowExperience = '';

    public string $rowMetExample = '';

    public string $rowUnmetExample = '';

    public string $rowReasoning = '';

    public string $rowAssignedOn = '';

    public string $rowOverrideReason = '';

    public bool $editingScore = false;

    public string $scoreInput = '';

    public string $scoreReason = '';

    public bool $showAggregationPreview = false;

    public function mount(Diagnosis $diagnosis): void
    {
        Gate::authorize('view', $diagnosis);

        $this->diagnosis = $diagnosis;
    }

    public function changeStatus(string $status, DiagnosisService $diagnoses): void
    {
        Gate::authorize('update', $this->diagnosis);

        $target = DiagnosisStatus::tryFrom($status);
        abort_if($target === null, 404);

        $this->attempt(function () use ($diagnoses, $target): void {
            $diagnoses->transition($this->diagnosis, $target);
            session()->flash('status', __('admin.diagnoses.status_changed', ['status' => $target->label()]));
        });
    }

    public function addValue(DiagnosisService $diagnoses): void
    {
        Gate::authorize('update', $this->diagnosis);

        $this->validate(['newValueName' => ['required', 'string', 'max:255']], attributes: [
            'newValueName' => __('admin.diagnoses.add_value_label'),
        ]);

        $this->attempt(function () use ($diagnoses): void {
            $row = $diagnoses->addValueByName($this->diagnosis, trim($this->newValueName));
            $this->newValueName = '';
            session()->flash('status', __('admin.diagnoses.value_added', ['name' => $row->value->name]));
        });
    }

    public function editRow(int $rowId): void
    {
        Gate::authorize('update', $this->diagnosis);

        $row = $this->findRow($rowId);

        $this->editingRowId = $row->id;
        $this->rowScore = match (true) {
            $row->score_state === ScoreState::NoTrace => DiagnosisService::NO_TRACE,
            $row->score === null => '',
            default => $this->numberInput($row->score),
        };
        $this->rowExperience = match (true) {
            $row->experience_state === ExperienceState::DontKnow => DiagnosisService::DONT_KNOW,
            $row->experience === null => '',
            default => $this->numberInput($row->experience),
        };
        $this->rowMetExample = (string) $row->experience_met_example;
        $this->rowUnmetExample = (string) $row->experience_unmet_example;
        $this->rowReasoning = (string) $row->reasoning;
        $this->rowAssignedOn = $row->assigned_on->toDateString();
        $this->rowOverrideReason = (string) $row->override_reason;
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        $this->editingRowId = null;
        $this->resetValidation();
    }

    public function saveRow(DiagnosisService $diagnoses): void
    {
        Gate::authorize('update', $this->diagnosis);

        $row = $this->findRow((int) $this->editingRowId);

        if ($row->automatic_score_state !== null) {
            $this->overrideRow($row, $diagnoses);

            return;
        }

        // The score is required on value rows, except in a Megélés diagnosis (04 · 4.4).
        $scoreRequired = $row->kind->value === 'value' && ! $this->diagnosis->type->isExperience();

        $validated = $this->validate([
            'rowScore' => [$scoreRequired ? 'required' : 'nullable', 'string', function (string $attribute, mixed $input, \Closure $fail): void {
                if ($input !== DiagnosisService::NO_TRACE && ! preg_match(self::NUMBER_PATTERN, (string) $input)) {
                    $fail(__('validation.in', ['attribute' => __('admin.diagnoses.score')]));
                }
            }],
            'rowExperience' => ['nullable', 'string', function (string $attribute, mixed $input, \Closure $fail): void {
                if ($input !== DiagnosisService::DONT_KNOW && ! preg_match(self::NUMBER_PATTERN, (string) $input)) {
                    $fail(__('validation.in', ['attribute' => __('admin.diagnoses.experience')]));
                }
            }],
            'rowMetExample' => ['nullable', 'string', 'max:2000'],
            'rowUnmetExample' => ['nullable', 'string', 'max:2000'],
            'rowReasoning' => ['nullable', 'string', 'max:2000'],
            'rowAssignedOn' => ['required', 'date'],
        ], attributes: [
            'rowScore' => __('admin.diagnoses.score'),
            'rowExperience' => __('admin.diagnoses.experience'),
            'rowMetExample' => __('admin.diagnoses.experience_met_example'),
            'rowUnmetExample' => __('admin.diagnoses.experience_unmet_example'),
            'rowReasoning' => __('admin.diagnoses.reasoning'),
            'rowAssignedOn' => __('admin.diagnoses.assigned_on'),
        ]);

        $this->attempt(function () use ($diagnoses, $row, $validated): void {
            $diagnoses->updateRow($row, [
                'score' => $validated['rowScore'],
                // A calculated diagnosis keeps the experience of its people (18 · 18.4).
                ...($this->diagnosis->isCalculated() ? [] : ['experience' => $validated['rowExperience']]),
                'experience_met_example' => filled($validated['rowMetExample']) ? $validated['rowMetExample'] : null,
                'experience_unmet_example' => filled($validated['rowUnmetExample']) ? $validated['rowUnmetExample'] : null,
                'reasoning' => filled($validated['rowReasoning']) ? $validated['rowReasoning'] : null,
                'assigned_on' => $validated['rowAssignedOn'],
            ]);

            $this->editingRowId = null;
            session()->flash('status', __('admin.diagnoses.row_saved'));
        });
    }

    /**
     * Back to the calculated value (13 · 13.4).
     */
    public function resetRow(int $rowId, DiagnosisService $diagnoses): void
    {
        Gate::authorize('update', $this->diagnosis);

        $row = $this->findRow($rowId);

        $this->attempt(function () use ($diagnoses, $row): void {
            $diagnoses->resetRowOverride($row);
            $this->editingRowId = null;
            session()->flash('status', __('admin.diagnoses.override.reset_done'));
        });
    }

    public function editScore(): void
    {
        Gate::authorize('update', $this->diagnosis);

        $this->editingScore = true;
        $this->scoreInput = match (true) {
            $this->diagnosis->score_state === ScoreState::NoTrace => DiagnosisService::NO_TRACE,
            $this->diagnosis->score === null => '',
            default => number_format($this->diagnosis->score, 1, '.', ''),
        };
        $this->scoreReason = (string) $this->diagnosis->score_override_reason;
        $this->resetValidation();
    }

    /**
     * The point of a process diagnosis, written over with a reason (13 · 13.5).
     */
    public function saveScore(DiagnosisService $diagnoses): void
    {
        Gate::authorize('update', $this->diagnosis);
        abort_unless($this->diagnosis->isCalculated() && $this->diagnosis->level === DiagnosisLevel::Process, 404);

        $validated = $this->validate([
            'scoreInput' => ['required', 'string', $this->scoreRule()],
            'scoreReason' => ['required', 'string', 'min:3', 'max:1000'],
        ], attributes: [
            'scoreInput' => __('admin.diagnoses.process_point'),
            'scoreReason' => __('admin.diagnoses.override.reason'),
        ]);

        $this->attempt(function () use ($diagnoses, $validated): void {
            $diagnoses->overrideScore($this->diagnosis, $validated['scoreInput'], $validated['scoreReason'], auth('admin')->user());
            $this->editingScore = false;
            session()->flash('status', __('admin.diagnoses.override.saved'));
        });
    }

    public function resetScore(DiagnosisService $diagnoses): void
    {
        Gate::authorize('update', $this->diagnosis);

        $this->attempt(function () use ($diagnoses): void {
            $diagnoses->resetScoreOverride($this->diagnosis);
            $this->editingScore = false;
            session()->flash('status', __('admin.diagnoses.override.reset_done'));
        });
    }

    public function removeRow(int $rowId, DiagnosisService $diagnoses): void
    {
        Gate::authorize('update', $this->diagnosis);

        $row = $this->findRow($rowId);

        $this->attempt(function () use ($diagnoses, $row): void {
            $diagnoses->removeRow($row);
            session()->flash('status', __('admin.diagnoses.row_removed'));
        });
    }

    /**
     * The admin may delete the recording; the transcript and the diagnosis stay (06 · 6.6).
     */
    public function deleteAudio(MaterialStore $materials): void
    {
        abort_if($this->diagnosis->material === null, 404);
        Gate::authorize('update', $this->diagnosis->material);

        $materials->deleteAudio($this->diagnosis->material);
        session()->flash('status', __('admin.materials.audio_deleted'));
    }

    public function previewAggregation(): void
    {
        Gate::authorize('update', $this->diagnosis);

        $this->showAggregationPreview = true;
    }

    public function cancelAggregation(): void
    {
        $this->showAggregationPreview = false;
    }

    public function loadAggregation(DiagnosisAggregator $aggregator): void
    {
        Gate::authorize('update', $this->diagnosis);

        $this->attempt(function () use ($aggregator): void {
            $aggregator->load($this->diagnosis);
            $this->showAggregationPreview = false;
            session()->flash('status', __('admin.diagnoses.aggregation.loaded'));
        });
    }

    public function render(DiagnosisAggregator $aggregator, ProcessOccasionBreakdown $breakdown): View
    {
        $this->diagnosis->refresh()->load([
            'organization', 'team', 'user', 'type', 'creator', 'transitions.creator', 'material',
            'main.leader', 'main.participants', 'parent', 'process', 'scoreOverrider',
        ]);

        $rows = $this->diagnosis->rows()->with(['value', 'valueVersion', 'process', 'area', 'overrider'])->get();
        $isProcess = $this->diagnosis->level === DiagnosisLevel::Process;
        $valueRows = $rows->where('kind.value', 'value')->values();

        $preview = null;

        if ($this->showAggregationPreview) {
            try {
                $preview = $aggregator->preview($this->diagnosis);
            } catch (DiagnosisException $exception) {
                session()->now('error', $exception->getMessage());
            }
        }

        return view('livewire.admin.diagnoses.show', [
            'valueRows' => $valueRows,
            'treeRows' => $rows->where('kind.value', 'tree_element')->values(),
            'processRows' => $rows->where('kind.value', 'process')->values(),
            'areaRows' => $rows->where('kind.value', 'area')->values(),
            'isCalculated' => $this->diagnosis->isCalculated(),
            'occasions' => $isProcess ? $breakdown->occasions($this->diagnosis) : collect(),
            'newOccasions' => $isProcess ? $breakdown->newSincePublishing($this->diagnosis) : 0,
            'isEditable' => $this->diagnosis->status->isEditable(),
            'main' => $this->diagnosis->main,
            // Status buttons only for diagnoses that move on their own (17 · 17.4, 17.5).
            'canChangeStatus' => $this->diagnosis->main === null && $this->diagnosis->parent_id === null,
            'availableValues' => $this->diagnosis->status->isEditable()
                ? Value::query()
                    ->availableTo($this->diagnosis->organization)
                    ->whereNotIn('id', $valueRows->pluck('value_id'))
                    ->orderBy('name')
                    ->pluck('name')
                : collect(),
            'preview' => $preview,
            'editingRow' => $this->editingRowId === null ? null : $rows->firstWhere('id', $this->editingRowId),
            // "Egyéb detektált értékek": what matched nothing, without a score (06 · 6.5).
            'otherDetected' => $this->diagnosis->material === null ? collect() : $this->diagnosis->material->detections()
                ->kept()
                ->whereNotNull('unmatched_value_id')
                ->when($this->diagnosis->speaker !== null, fn ($query) => $query->where('speaker', $this->diagnosis->speaker))
                ->with('unmatchedValue')
                ->get()
                ->groupBy('unmatched_value_id'),
        ])->layout('layouts::admin')->title($this->diagnosis->name);
    }

    /**
     * Runs a change and shows a broken rule as an error message.
     */
    private function attempt(callable $change): void
    {
        try {
            $change();
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * A calculated row written over: the score and a reason (13 · 13.5).
     */
    private function overrideRow(DiagnosisRow $row, DiagnosisService $diagnoses): void
    {
        $validated = $this->validate([
            'rowScore' => ['required', 'string', $this->scoreRule()],
            'rowOverrideReason' => ['required', 'string', 'min:3', 'max:1000'],
            'rowReasoning' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'rowScore' => __('admin.diagnoses.score'),
            'rowOverrideReason' => __('admin.diagnoses.override.reason'),
            'rowReasoning' => __('admin.diagnoses.reasoning'),
        ]);

        $this->attempt(function () use ($diagnoses, $row, $validated): void {
            $diagnoses->overrideRow($row, $validated['rowScore'], $validated['rowOverrideReason'], auth('admin')->user());
            $row->update(['reasoning' => filled($validated['rowReasoning']) ? $validated['rowReasoning'] : null]);

            $this->editingRowId = null;
            session()->flash('status', __('admin.diagnoses.override.saved'));
        });
    }

    private function scoreRule(): \Closure
    {
        return function (string $attribute, mixed $input, \Closure $fail): void {
            if ($input !== DiagnosisService::NO_TRACE && ! preg_match(self::NUMBER_PATTERN, (string) $input)) {
                $fail(__('validation.in', ['attribute' => __('admin.diagnoses.score')]));
            }
        };
    }

    private function findRow(int $rowId): DiagnosisRow
    {
        return $this->diagnosis->rows()->whereKey($rowId)->firstOrFail();
    }

    private function numberInput(float $number): string
    {
        return fmod($number, 1.0) === 0.0 ? (string) (int) $number : number_format($number, 1, '.', '');
    }
}
