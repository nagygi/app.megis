<?php

namespace App\Livewire\Admin\CapabilityGroups;

use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\CapabilityGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Képességtár → Csoportok (07 · 7.4).
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', CapabilityGroup::class);
    }

    /**
     * Only a group without capabilities may be deleted.
     */
    public function delete(int $groupId): void
    {
        $group = CapabilityGroup::query()->withCount('capabilities')->findOrFail($groupId);
        Gate::authorize('update', $group);

        if ($group->capabilities_count > 0) {
            session()->flash('error', __('admin.capability_groups.cannot_delete'));

            return;
        }

        $group->delete();
        session()->flash('status', __('admin.common.deleted'));
    }

    public function render(): View
    {
        $query = CapabilityGroup::query()->withCount('capabilities');

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search->whereLike('name', $term)
                ->orWhereLike('name_en', $term)
                ->orWhereLike('description', $term));
        }

        return view('livewire.admin.capability-groups.index', [
            'groups' => $this->applySorting($query)->paginate(25),
        ])->layout('layouts::admin')->title(__('admin.capability_groups.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'name_en' => 'name_en',
            'capabilities' => 'capabilities_count',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }
}
