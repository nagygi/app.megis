<?php

namespace App\Livewire\Admin\Organizations;

use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Organization;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The organization's "Csapatok" tab (02 · 2.4). A private person has no teams.
 */
class Teams extends Component
{
    use WithDataTable;

    public Organization $organization;

    public ?int $editingTeamId = null;

    public bool $showForm = false;

    public string $name = '';

    public string $description = '';

    public function mount(Organization $organization): void
    {
        Gate::authorize('update', $organization);
        abort_if($organization->isIndividual(), 404);

        $this->organization = $organization;
    }

    public function create(): void
    {
        $this->reset(['editingTeamId', 'name', 'description']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $teamId): void
    {
        $team = $this->findTeam($teamId);

        $this->editingTeamId = $team->id;
        $this->name = $team->name;
        $this->description = (string) $team->description;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset(['editingTeamId', 'name', 'description', 'showForm']);
    }

    public function save(): void
    {
        Gate::authorize('update', $this->organization);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'name')
                    ->where('organization_id', $this->organization->id)
                    ->ignore($this->editingTeamId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
        ], ['name.unique' => __('admin.teams.name_taken')]);

        $attributes = [
            'name' => trim($validated['name']),
            'description' => filled($validated['description']) ? $validated['description'] : null,
        ];

        $this->editingTeamId === null
            ? $this->organization->teams()->create($attributes)
            : $this->findTeam($this->editingTeamId)->update($attributes);

        $this->cancel();
        session()->flash('status', __('admin.common.saved'));
    }

    /**
     * Members stay in the organization; only their team assignment goes (02 · 2.4).
     */
    public function delete(int $teamId): void
    {
        Gate::authorize('update', $this->organization);

        $this->findTeam($teamId)->delete();
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
        ])->layout('layouts::admin', ['organization' => $this->organization])->title($this->organization->name);
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

    private function findTeam(int $teamId): Team
    {
        return $this->organization->teams()->findOrFail($teamId);
    }
}
