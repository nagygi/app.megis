<?php

namespace App\Livewire\Admin\Organizations;

use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The organization's "Csapatok" tab (02 · 2.4). A private person has no teams.
 * A team is created and edited on a page of its own (TeamForm).
 */
class Teams extends Component
{
    use WithDataTable;

    public Organization $organization;

    public function mount(Organization $organization): void
    {
        Gate::authorize('update', $organization);
        abort_if($organization->isIndividual(), 404);

        $this->organization = $organization;
    }

    /**
     * Members stay in the organization; only their team assignment goes (02 · 2.4).
     */
    public function delete(int $teamId): void
    {
        Gate::authorize('update', $this->organization);

        $this->organization->teams()->findOrFail($teamId)->delete();
        session()->flash('status', __('admin.common.deleted'));
    }

    public function render(): View
    {
        $query = $this->organization->teams()
            ->getQuery()
            ->withCount('users')
            ->with('leaders');

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search->whereLike('name', $term)->orWhereLike('description', $term));
        }

        return view('livewire.admin.organizations.teams', [
            'teams' => $this->applySorting($query)->paginate(25),
        ])->layout('layouts::admin')->title($this->organization->name);
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'users' => 'users_count',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }
}
