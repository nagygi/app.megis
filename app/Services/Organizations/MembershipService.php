<?php

namespace App\Services\Organizations;

use App\Enums\MembershipStatus;
use App\Enums\TokenPurpose;
use App\Exceptions\HeadcountLimitReachedException;
use App\Mail\ClientInvitationMail;
use App\Mail\OrganizationAddedMail;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Users of an organization (02 · 2.5): adding, inviting, tags, teams and the headcount limit.
 *
 * @phpstan-type MemberData array{
 *     email?: string,
 *     first_name?: ?string,
 *     last_name?: ?string,
 *     position?: ?string,
 *     is_organization_leader: bool,
 *     is_employee: bool,
 *     is_partner: bool,
 *     teams: array<int, bool>,
 * }
 */
class MembershipService
{
    public function __construct(private TokenService $tokens) {}

    /**
     * An existing account joins the organization instead of a new one being created.
     *
     * @param  MemberData  $data
     *
     * @throws HeadcountLimitReachedException
     */
    public function add(Organization $organization, array $data, User $actor, bool $sendNow): Membership
    {
        $membership = DB::transaction(function () use ($organization, $data): Membership {
            $this->ensureFreeSeat($organization);

            $user = User::query()->firstWhere('email', Str::lower(trim($data['email'])));

            if ($user === null) {
                $user = User::create([
                    'email' => $data['email'],
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                ]);
            }

            $membership = Membership::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'status' => MembershipStatus::Added,
                ...$this->membershipAttributes($organization, $data),
            ]);

            $this->syncTeams($membership, $data['teams']);

            return $membership;
        });

        if ($sendNow) {
            $this->sendInvitation($membership, $actor);
        }

        return $membership;
    }

    /**
     * New accounts get a registration link valid for 14 days; existing accounts
     * get a notice and can enter right away (02 · 2.5, 14 · 14.1).
     */
    public function sendInvitation(Membership $membership, User $actor): void
    {
        $user = $membership->user;
        $organization = $membership->organization;

        if ($user->isActivated()) {
            $membership->forceFill([
                'status' => MembershipStatus::Active,
                'activated_at' => $membership->activated_at ?? now(),
            ])->save();

            Mail::to($user->email)->queue(new OrganizationAddedMail($user, $organization));

            return;
        }

        $issued = $this->tokens->issue($user, TokenPurpose::ClientInvitation, $organization, $actor);

        $membership->forceFill([
            'status' => MembershipStatus::Invited,
            'invited_at' => now(),
        ])->save();

        Mail::to($user->email)->queue(new ClientInvitationMail($user, $organization, $actor, $issued->plainToken));
    }

    /**
     * @param  MemberData  $data
     */
    public function update(Membership $membership, array $data): void
    {
        DB::transaction(function () use ($membership, $data): void {
            $membership->update($this->membershipAttributes($membership->organization, $data));
            $this->syncTeams($membership, $data['teams']);
        });
    }

    /**
     * The user loses access; recorded diagnosis data stays (02 · 2.5).
     */
    public function deactivate(Membership $membership): void
    {
        $membership->forceFill([
            'status' => MembershipStatus::Inactive,
            'deactivated_at' => now(),
        ])->save();

        $this->tokens->invalidatePending($membership->user, TokenPurpose::ClientInvitation, $membership->organization);
    }

    /**
     * Only possible while there is a free seat. A user who never registered
     * goes back to "Felvéve" and needs a new invitation.
     *
     * @throws HeadcountLimitReachedException
     */
    public function activate(Membership $membership): void
    {
        DB::transaction(function () use ($membership): void {
            $this->ensureFreeSeat($membership->organization);

            $membership->forceFill([
                'status' => $membership->user->isActivated() ? MembershipStatus::Active : MembershipStatus::Added,
                'deactivated_at' => null,
            ])->save();
        });
    }

    /**
     * @param  MemberData  $data
     * @return array<string, mixed>
     */
    private function membershipAttributes(Organization $organization, array $data): array
    {
        // A private person's only user is always the organization leader (02 · 2.3).
        $isIndividual = $organization->isIndividual();

        return [
            'position' => filled($data['position'] ?? null) ? trim($data['position']) : null,
            'is_organization_leader' => $isIndividual || $data['is_organization_leader'],
            'is_employee' => ! $isIndividual && $data['is_employee'],
            'is_partner' => ! $isIndividual && $data['is_partner'],
        ];
    }

    /**
     * @param  array<int, bool>  $teamLeaderFlags  team id => is the user its leader
     */
    private function syncTeams(Membership $membership, array $teamLeaderFlags): void
    {
        $organizationTeamIds = Team::query()->whereBelongsTo($membership->organization)->pluck('id');

        $selected = collect($teamLeaderFlags)
            ->filter(fn (bool $isLeader, int $teamId): bool => $organizationTeamIds->contains($teamId))
            ->mapWithKeys(fn (bool $isLeader, int $teamId): array => [$teamId => ['is_leader' => $isLeader]]);

        $user = $membership->user;
        $user->teams()->detach($organizationTeamIds->diff($selected->keys())->all());
        $user->teams()->syncWithoutDetaching($selected->all());
    }

    /**
     * @throws HeadcountLimitReachedException
     */
    private function ensureFreeSeat(Organization $organization): void
    {
        $lockedOrganization = Organization::query()->whereKey($organization->id)->lockForUpdate()->firstOrFail();

        if (! $lockedOrganization->hasFreeSeat()) {
            throw new HeadcountLimitReachedException($lockedOrganization);
        }
    }
}
