<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\MembershipStatus;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\Organizations\MembershipService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /**
     * @var list<int>
     */
    public array $userIds = [];

    /**
     * @var list<int>
     */
    public array $leaderUserIds = [];

    public function mount(Organization $organization): void
    {
        Gate::authorize('update', $organization);
        abort_if($organization->isIndividual(), 404);

        $this->organization = $organization;
    }

    public function create(): void
    {
        $this->reset(['editingTeamId', 'name', 'description', 'userIds', 'leaderUserIds']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $teamId): void
    {
        $team = $this->findTeam($teamId);
        $users = $team->users()->get();

        $this->editingTeamId = $team->id;
        $this->name = $team->name;
        $this->description = (string) $team->description;
        $this->userIds = $users->pluck('id')->all();
        $this->leaderUserIds = $users->filter(fn (User $user) => (bool) $user->pivot->is_leader)->pluck('id')->values()->all();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset(['editingTeamId', 'name', 'description', 'userIds', 'leaderUserIds', 'showForm']);
    }

    public function save(MembershipService $memberships): void
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
            'userIds' => ['array'],
            'userIds.*' => ['integer'],
            'leaderUserIds' => ['array'],
            'leaderUserIds.*' => ['integer'],
        ], ['name.unique' => __('admin.teams.name_taken')]);

        $attributes = [
            'name' => trim($validated['name']),
            'description' => filled($validated['description']) ? $validated['description'] : null,
        ];

        DB::transaction(function () use ($attributes, $memberships): void {
            if ($this->editingTeamId === null) {
                $team = $this->organization->teams()->create($attributes);
            } else {
                $team = $this->findTeam($this->editingTeamId);
                $team->update($attributes);
            }

            $memberships->syncTeamMembers($team, $this->userLeaderFlags());
        });

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
            'members' => $this->showForm ? $this->memberOptions() : collect(),
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

    /**
     * Users already in the organization. An inactive one is offered only while
     * still in the team, so saving does not drop them unnoticed.
     *
     * @return Collection<int, Membership>
     */
    private function memberOptions(): Collection
    {
        return Membership::query()
            ->where('organization_id', $this->organization->id)
            ->where(fn (Builder $members) => $members
                ->where('status', '!=', MembershipStatus::Inactive)
                ->orWhereIn('user_id', array_map('intval', $this->userIds)))
            ->with('user')
            ->get()
            ->sortBy(fn (Membership $membership) => $membership->user->name, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return array<int, bool> user id => is the user the team's leader
     */
    private function userLeaderFlags(): array
    {
        return collect($this->userIds)
            ->map(fn ($userId): int => (int) $userId)
            ->mapWithKeys(fn (int $userId): array => [$userId => in_array($userId, array_map('intval', $this->leaderUserIds), true)])
            ->all();
    }
}
