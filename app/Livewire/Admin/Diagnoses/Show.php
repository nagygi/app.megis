<?php

namespace App\Livewire\Admin\Diagnoses;

use App\Enums\DiagnosisStatus;
use App\Enums\ExperienceState;
use App\Enums\ScoreState;
use App\Exceptions\DiagnosisException;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\Value;
use App\Services\Diagnoses\DiagnosisAggregator;
use App\Services\Diagnoses\DiagnosisService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * A diagnosis with its two blocks, values and the brand base (04 · 4.4),
 * the status steps with their log (4.3) and the aggregated data (4.5).
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
                'experience' => $validated['rowExperience'],
                'experience_met_example' => filled($validated['rowMetExample']) ? $validated['rowMetExample'] : null,
                'experience_unmet_example' => filled($validated['rowUnmetExample']) ? $validated['rowUnmetExample'] : null,
                'reasoning' => filled($validated['rowReasoning']) ? $validated['rowReasoning'] : null,
                'assigned_on' => $validated['rowAssignedOn'],
            ]);

            $this->editingRowId = null;
            session()->flash('status', __('admin.diagnoses.row_saved'));
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

    public function render(DiagnosisAggregator $aggregator): View
    {
        $this->diagnosis->refresh()->load(['organization', 'team', 'user', 'type', 'creator', 'transitions.creator']);

        $rows = $this->diagnosis->rows()->with(['value', 'valueVersion'])->get();
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
            'isEditable' => $this->diagnosis->status->isEditable(),
            'availableValues' => $this->diagnosis->status->isEditable()
                ? Value::query()
                    ->availableTo($this->diagnosis->organization)
                    ->whereNotIn('id', $valueRows->pluck('value_id'))
                    ->orderBy('name')
                    ->pluck('name')
                : collect(),
            'preview' => $preview,
            'editingRow' => $this->editingRowId === null ? null : $rows->firstWhere('id', $this->editingRowId),
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

    private function findRow(int $rowId): DiagnosisRow
    {
        return $this->diagnosis->rows()->whereKey($rowId)->firstOrFail();
    }

    private function numberInput(float $number): string
    {
        return fmod($number, 1.0) === 0.0 ? (string) (int) $number : number_format($number, 1, '.', '');
    }
}
