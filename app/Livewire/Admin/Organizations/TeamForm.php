<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\Organizations\MembershipService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Creating or editing a team of the organization, on a page of its own under
 * the Csapatok tab (02 · 2.4): its members in a list, more added by search.
 */
class TeamForm extends Component
{
    public Organization $organization;

    public ?Team $team = null;

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

    public string $memberSearch = '';

    public function mount(Organization $organization, ?Team $team = null): void
    {
        Gate::authorize('update', $organization);
        abort_if($organization->isIndividual(), 404);

        $this->organization = $organization;

        if ($team?->exists) {
            $users = $team->users()->get();

            $this->team = $team;
            $this->name = $team->name;
            $this->description = (string) $team->description;
            $this->userIds = $users->pluck('id')->all();
            $this->leaderUserIds = $users->filter(fn (User $user) => (bool) $user->pivot->is_leader)->pluck('id')->values()->all();
        }
    }

    /**
     * Only a user of the organization who is not inactive joins.
     */
    public function addMember(int $userId): void
    {
        if ($this->candidates($this->memberships())->contains('user_id', $userId)) {
            $this->userIds[] = $userId;
        }

        $this->memberSearch = '';
    }

    /**
     * Enter in the search: the first match of what is typed, even before the
     * debounced search has reached the server.
     */
    public function addFirstCandidate(string $search): void
    {
        $this->memberSearch = $search;
        $first = $this->candidates($this->memberships(), $search)->first();

        if ($first !== null) {
            $this->addMember($first->user_id);
        }
    }

    public function removeMember(int $userId): void
    {
        $this->userIds = array_values(array_filter($this->userIds, fn ($id): bool => (int) $id !== $userId));
        $this->leaderUserIds = array_values(array_filter($this->leaderUserIds, fn ($id): bool => (int) $id !== $userId));
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
                    ->ignore($this->team?->id),
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
            if ($this->team === null) {
                $team = $this->organization->teams()->create($attributes);
            } else {
                $team = $this->team;
                $team->update($attributes);
            }

            $memberships->syncTeamMembers($team, $this->userLeaderFlags());
        });

        session()->flash('status', __('admin.common.saved'));
        $this->redirectRoute('admin.organizations.teams', $this->organization, navigate: false);
    }

    public function render(): View
    {
        $memberships = $this->memberships();

        return view('livewire.admin.organizations.team-form', [
            'isEditing' => $this->team !== null,
            'hasOrganizationMembers' => $memberships->isNotEmpty(),
            // An inactive member stays in the list until taken out, so saving does not drop them unnoticed.
            'members' => $memberships->filter(fn (Membership $membership) => $this->isSelected($membership->user_id))->values(),
            'candidates' => $this->candidates($memberships, $this->memberSearch),
            'hasCandidates' => $this->candidates($memberships)->isNotEmpty(),
        ])->layout('layouts::admin')->title($this->team?->name ?? __('admin.teams.create'));
    }

    /**
     * The users of the organization, by name.
     *
     * @return Collection<int, Membership>
     */
    private function memberships(): Collection
    {
        return Membership::query()
            ->where('organization_id', $this->organization->id)
            ->with('user')
            ->get()
            ->sortBy(fn (Membership $membership) => $membership->user->name, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Who can still join: not in the team yet and not inactive, matching the
     * search by name or e-mail.
     *
     * @param  Collection<int, Membership>  $memberships
     * @return Collection<int, Membership>
     */
    private function candidates(Collection $memberships, string $search = ''): Collection
    {
        $term = Str::lower(trim($search));

        return $memberships
            ->reject(fn (Membership $membership) => $this->isSelected($membership->user_id) || $membership->status === MembershipStatus::Inactive)
            ->filter(fn (Membership $membership) => $term === '' || Str::contains(Str::lower($membership->user->name.' '.$membership->user->email), $term))
            ->values();
    }

    private function isSelected(int $userId): bool
    {
        return in_array($userId, array_map('intval', $this->userIds), true);
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
