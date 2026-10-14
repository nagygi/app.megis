<?php

namespace App\Livewire\Admin\Diagnoses;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisStatus;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Diagnózisok (04 · 4.2).
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', Diagnosis::class);
    }

    public function render(): View
    {
        $query = Diagnosis::query()->with(['organization', 'team', 'user', 'type', 'creator']);

        if ($term = $this->searchTerm()) {
            $query->whereLike('name', $term);
        }

        if ($organization = $this->filter('organization')) {
            $query->where('organization_id', $organization);
        }

        if ($type = $this->filter('type')) {
            $query->where('diagnosis_type_id', $type);
        }

        foreach (['level' => DiagnosisLevel::class, 'creator_kind' => DiagnosisCreator::class, 'status' => DiagnosisStatus::class] as $column => $enum) {
            if (($case = $enum::tryFrom((string) $this->filter($column))) !== null) {
                $query->where($column, $case);
            }
        }

        $enumOptions = fn (string $enum) => collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();

        return view('livewire.admin.diagnoses.index', [
            'diagnoses' => $this->applySorting($query)->paginate(25),
            'organizations' => Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
            'types' => DiagnosisType::query()->orderBy('name')->pluck('name', 'id')->all(),
            'levels' => $enumOptions(DiagnosisLevel::class),
            'creators' => $enumOptions(DiagnosisCreator::class),
            'statuses' => $enumOptions(DiagnosisStatus::class),
        ])->layout('layouts::admin')->title(__('admin.diagnoses.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'organization' => Organization::query()->select('name')->whereColumn('organizations.id', 'diagnoses.organization_id'),
            'level' => 'level',
            'type' => DiagnosisType::query()->select('name')->whereColumn('diagnosis_types.id', 'diagnoses.diagnosis_type_id'),
            'date' => 'diagnosed_on',
            'creator' => 'creator_kind',
            'status' => 'status',
        ];
    }

    protected function defaultSort(): array
    {
        return ['date', 'desc'];
    }
}
