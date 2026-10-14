<?php

namespace App\Livewire\Admin\Organizations;

use App\Exceptions\HeadcountLimitReachedException;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Services\Organizations\MembershipService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Inviting a user to the organization, or editing position, tags and teams (02 · 2.5).
 */
class MemberForm extends Component
{
    public Organization $organization;

    public ?Membership $membership = null;

    /**
     * Preselected team when coming from a team's row (02 · 2.5).
     */
    #[Url(as: 'csapat')]
    public ?int $presetTeamId = null;

    public string $email = '';

    public string $last_name = '';

    public string $first_name = '';

    public string $position = '';

    public bool $isOrganizationLeader = false;

    public bool $isEmployee = false;

    public bool $isPartner = false;

    /**
     * @var list<int>
     */
    public array $teamIds = [];

    /**
     * @var list<int>
     */
    public array $leaderTeamIds = [];

    public bool $sendNow = false;

    public function mount(Organization $organization, ?Membership $membership = null): void
    {
        Gate::authorize('update', $organization);
        $this->organization = $organization;

        if ($membership?->exists) {
            $this->membership = $membership->load('user');
            $this->email = $membership->user->email;
            $this->position = (string) $membership->position;
            $this->isOrganizationLeader = $membership->is_organization_leader;
            $this->isEmployee = $membership->is_employee;
            $this->isPartner = $membership->is_partner;

            $teams = $membership->user->teams()->where('teams.organization_id', $organization->id)->get();
            $this->teamIds = $teams->pluck('id')->all();
            $this->leaderTeamIds = $teams->filter(fn (Team $team) => (bool) $team->pivot->is_leader)->pluck('id')->values()->all();

            return;
        }

        if ($this->presetTeamId !== null && $organization->teams()->whereKey($this->presetTeamId)->exists()) {
            $this->teamIds = [$this->presetTeamId];
        }
    }

    public function save(MembershipService $memberships): void
    {
        Gate::authorize('update', $this->organization);

        $isIndividual = $this->organization->isIndividual();

        $this->validate([
            'email' => $this->membership ? [] : ['required', 'string', 'email', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'teamIds' => ['array'],
            'teamIds.*' => ['integer'],
            'leaderTeamIds' => ['array'],
            'leaderTeamIds.*' => ['integer'],
        ]);

        if (! $isIndividual && ! $this->isOrganizationLeader && ! $this->isEmployee && ! $this->isPartner) {
            $this->addError('tags', __('admin.members.tags_required'));

            return;
        }

        $data = [
            'email' => $this->email,
            'first_name' => $this->first_name ?: null,
            'last_name' => $this->last_name ?: null,
            'position' => $this->position,
            'is_organization_leader' => $this->isOrganizationLeader,
            'is_employee' => $this->isEmployee,
            'is_partner' => $this->isPartner,
            'teams' => $isIndividual ? [] : $this->teamLeaderFlags(),
        ];

        if ($this->membership !== null) {
            $memberships->update($this->membership, $data);

            session()->flash('status', __('admin.common.saved'));
            $this->redirectRoute('admin.organizations.members', $this->organization, navigate: false);

            return;
        }

        $alreadyMember = $this->organization->memberships()
            ->whereHas('user', fn ($users) => $users->where('email', Str::lower(trim($this->email))))
            ->exists();

        if ($alreadyMember) {
            $this->addError('email', __('admin.members.already_member'));

            return;
        }

        try {
            $memberships->add($this->organization, $data, auth()->user(), $this->sendNow);
        } catch (HeadcountLimitReachedException $exception) {
            $this->addError('email', $exception->getMessage());

            return;
        }

        session()->flash('status', $this->sendNow
            ? __('admin.members.invitation_sent', ['email' => Str::lower(trim($this->email))])
            : __('admin.members.added'));
        $this->redirectRoute('admin.organizations.members', $this->organization, navigate: false);
    }

    public function render(): View
    {
        return view('livewire.admin.organizations.member-form', [
            'isEditing' => $this->membership !== null,
            'teams' => $this->organization->teams()->orderBy('name')->get(['id', 'name']),
            'positions' => Membership::query()
                ->whereNotNull('position')
                ->distinct()
                ->orderBy('position')
                ->limit(200)
                ->pluck('position'),
        ])->layout('layouts::admin', ['organization' => $this->organization])->title($this->organization->name);
    }

    /**
     * @return array<int, bool> team id => is the user its leader
     */
    private function teamLeaderFlags(): array
    {
        return collect($this->teamIds)
            ->map(fn ($teamId): int => (int) $teamId)
            ->mapWithKeys(fn (int $teamId): array => [$teamId => in_array($teamId, array_map('intval', $this->leaderTeamIds), true)])
            ->all();
    }
}
