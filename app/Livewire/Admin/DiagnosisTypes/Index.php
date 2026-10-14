<?php

namespace App\Livewire\Admin\DiagnosisTypes;

use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\DiagnosisType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Diagnózisok → Diagnózis típusok (04 · 4.1).
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', DiagnosisType::class);
    }

    /**
     * Only an unused type may be deleted; the types the code relies on never.
     */
    public function delete(int $typeId): void
    {
        $type = DiagnosisType::query()->withCount('diagnoses')->findOrFail($typeId);
        Gate::authorize('update', $type);

        if ($type->isSystemType()) {
            session()->flash('error', __('admin.diagnosis_types.system'));

            return;
        }

        if ($type->diagnoses_count > 0) {
            session()->flash('error', __('admin.diagnosis_types.cannot_delete'));

            return;
        }

        $type->delete();
        session()->flash('status', __('admin.common.deleted'));
    }

    public function render(): View
    {
        $query = DiagnosisType::query()->withCount('diagnoses');

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search->whereLike('name', $term)->orWhereLike('description', $term));
        }

        return view('livewire.admin.diagnosis-types.index', [
            'types' => $this->applySorting($query)->paginate(25),
        ])->layout('layouts::admin')->title(__('admin.diagnosis_types.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'diagnoses' => 'diagnoses_count',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }
}
