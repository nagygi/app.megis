<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\MembershipStatus;
use App\Enums\TokenPurpose;
use App\Exceptions\HeadcountLimitReachedException;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\UserToken;
use App\Services\Organizations\MembershipService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The organization's "Felhasználók" tab (02 · 2.5).
 */
class Members extends Component
{
    use WithDataTable;

    public Organization $organization;

    public function mount(Organization $organization): void
    {
        Gate::authorize('update', $organization);

        $this->organization = $organization;
    }

    public function sendInvitation(int $membershipId, MembershipService $memberships): void
    {
        $membership = $this->findMembership($membershipId);
        Gate::authorize('update', $membership);

        $memberships->sendInvitation($membership, auth()->user());
        session()->flash('status', __('admin.members.invitation_sent', ['email' => $membership->user->email]));
    }

    public function deactivate(int $membershipId, MembershipService $memberships): void
    {
        $membership = $this->findMembership($membershipId);
        Gate::authorize('update', $membership);

        $memberships->deactivate($membership);
        session()->flash('status', __('admin.common.saved'));
    }

    public function activate(int $membershipId, MembershipService $memberships): void
    {
        $membership = $this->findMembership($membershipId);
        Gate::authorize('update', $membership);

        try {
            $memberships->activate($membership);
            session()->flash('status', __('admin.common.saved'));
        } catch (HeadcountLimitReachedException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $organizationId = $this->organization->id;

        $query = Membership::query()
            ->select('organization_user.*')
            ->join('users', 'users.id', '=', 'organization_user.user_id')
            ->where('organization_user.organization_id', $organizationId)
            ->with(['user.teams' => fn ($teams) => $teams->where('teams.organization_id', $organizationId)])
            ->addSelect(['has_pending_invitation' => UserToken::query()
                ->selectRaw('count(*) > 0')
                ->whereColumn('user_tokens.user_id', 'organization_user.user_id')
                ->where('user_tokens.organization_id', $organizationId)
                ->where('purpose', TokenPurpose::ClientInvitation)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->where('expires_at', '>', now())]);

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search
                ->whereLike('users.email', $term)
                ->orWhereLike('users.first_name', $term)
                ->orWhereLike('users.last_name', $term));
        }

        if ($status = MembershipStatus::tryFrom((string) $this->filter('status'))) {
            $query->where('organization_user.status', $status);
        }

        match ($this->filter('tag')) {
            'organization_leader' => $query->where('is_organization_leader', true),
            'employee' => $query->where('is_employee', true),
            'partner' => $query->where('is_partner', true),
            default => null,
        };

        if ($teamId = $this->filter('team')) {
            $query->whereExists(fn ($teamMembers) => $teamMembers
                ->from('team_user')
                ->whereColumn('team_user.user_id', 'organization_user.user_id')
                ->where('team_user.team_id', $teamId));
        }

        return view('livewire.admin.organizations.members', [
            'memberships' => $this->applySorting($query)->paginate(25),
            'headcountLabel' => $this->organization->headcountLabel(),
            'hasFreeSeat' => $this->organization->hasFreeSeat(),
            'limitMessage' => __('admin.members.limit_reached', [
                'count' => $this->organization->headcount(),
                'limit' => $this->organization->effectiveHeadcountLimit(),
            ]),
            'statusOptions' => collect(MembershipStatus::cases())->mapWithKeys(fn (MembershipStatus $status) => [$status->value => $status->label()]),
            'tagOptions' => [
                'organization_leader' => __('enums.membership_tag.organization_leader'),
                'employee' => __('enums.membership_tag.employee'),
                'partner' => __('enums.membership_tag.partner'),
            ],
            'teamOptions' => Team::query()->whereBelongsTo($this->organization)->orderBy('name')->pluck('name', 'id'),
        ])->layout('layouts::admin', ['organization' => $this->organization])->title($this->organization->name);
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'users.last_name',
            'email' => 'users.email',
            'position' => 'organization_user.position',
            'status' => 'organization_user.status',
            'last_login' => 'users.last_login_at',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }

    /**
     * Why "Belépés a nevében" is greyed out in the row, or null when it works:
     * the customer has to reach this organization on the customer side (14 · 14.6).
     */
    public function impersonationUnavailableReason(Membership $membership): ?string
    {
        $user = $membership->user;

        if ($membership->status === MembershipStatus::Active && Gate::allows('impersonate', $user)) {
            return null;
        }

        return match (true) {
            ! $user->isActive() => __('admin.members.impersonation_unavailable.user_inactive'),
            $membership->status === MembershipStatus::Inactive => __('admin.members.impersonation_unavailable.membership_inactive'),
            ! $user->isActivated() => __('admin.members.impersonation_unavailable.not_registered'),
            default => __('admin.members.impersonation_unavailable.membership_pending'),
        };
    }

    private function findMembership(int $membershipId): Membership
    {
        return Membership::query()
            ->where('organization_id', $this->organization->id)
            ->with(['user', 'organization'])
            ->findOrFail($membershipId);
    }
}
