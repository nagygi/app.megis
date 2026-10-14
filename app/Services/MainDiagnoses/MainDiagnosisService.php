<?php

namespace App\Services\MainDiagnoses;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisStep;
use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Enums\MembershipStatus;
use App\Enums\ParticipantRole;
use App\Exceptions\DiagnosisException;
use App\Jobs\RecalculateMainDiagnosis;
use App\Mail\DiagnosisLeaderInvitationMail;
use App\Mail\DiagnosisPeerInvitationMail;
use App\Models\DiagnosisType;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\Diagnoses\DiagnosisService;
use App\Services\Inbox\InboxService;
use App\Services\Organizations\MembershipService;
use App\Services\Organizations\OrganizationValueSetService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Starting and changing a main diagnosis (17): A1, the leader's invitation
 * (the first gate), "Indul a diagnózis" with the peers' invitations (23 · 23.4,
 * 25 · 25.1), changes until reading (17 · 17.7) and closing.
 *
 * @phpstan-type MainDiagnosisData array{
 *     organization_id: int,
 *     subject: string,
 *     team_id?: ?int,
 *     new_team_name?: ?string,
 *     leader_id?: ?int,
 *     new_leader?: ?array{first_name: string, last_name: string, email: string},
 *     excluded_peer_ids?: list<int>,
 *     starts_on?: ?string,
 *     publishes_on: string,
 *     show_peer_quotes: bool,
 *     name?: ?string,
 * }
 */
class MainDiagnosisService
{
    public function __construct(
        private DiagnosisService $diagnoses,
        private MembershipService $memberships,
        private OrganizationValueSetService $valueSets,
        private StageMachine $stages,
        private StepProgress $progress,
    ) {}

    /**
     * "Mentés vázlatként" on A1: nothing is sent yet.
     *
     * @param  MainDiagnosisData  $data
     */
    public function create(array $data, User $actor): MainDiagnosis
    {
        $organization = Organization::query()->with('product')->findOrFail($data['organization_id']);
        $product = $organization->product;

        if ($product === null || ! $product->hasDiagnosisFlow()) {
            throw new DiagnosisException(__('admin.main_diagnoses.no_flow'));
        }

        return DB::transaction(function () use ($data, $actor, $organization, $product): MainDiagnosis {
            $subject = $organization->isIndividual() ? MainDiagnosisSubject::Individual : MainDiagnosisSubject::from($data['subject']);
            $team = $subject === MainDiagnosisSubject::Team ? $this->resolveTeam($organization, $data) : null;
            $leader = $this->resolveLeader($organization, $subject, $team, $data, $actor);
            $startsOn = filled($data['starts_on'] ?? null) ? CarbonImmutable::parse($data['starts_on']) : null;

            $diagnosis = $this->diagnoses->create([
                'name' => filled($data['name'] ?? null)
                    ? trim($data['name'])
                    : $this->defaultName($organization, $subject, $team, $leader, $product->name, $startsOn),
                'organization_id' => $organization->id,
                'level' => $subject->level(),
                'team_id' => $team?->id,
                'user_id' => $subject === MainDiagnosisSubject::Person ? $leader->id : null,
                'diagnosis_type_id' => DiagnosisType::byKey(DiagnosisType::MAIN)->id,
                'diagnosed_on' => ($startsOn ?? today())->toDateString(),
                'diagnosed_until' => $data['publishes_on'],
            ], DiagnosisCreator::Admin);

            $main = MainDiagnosis::create([
                'diagnosis_id' => $diagnosis->id,
                'subject' => $subject,
                'product_id' => $product->id,
                'product_key' => $product->key,
                'product_name' => $product->name,
                'leader_id' => $leader->id,
                'stage' => MainDiagnosisStage::Draft,
                'starts_on' => $startsOn?->toDateString(),
                'publishes_on' => $data['publishes_on'],
                'show_peer_quotes' => $data['show_peer_quotes'],
            ]);

            $main->participants()->create(['user_id' => $leader->id, 'role' => ParticipantRole::Leader]);
            $this->syncPeers($main, $data['excluded_peer_ids'] ?? []);

            $diagnosis->transitions()->latest('id')->first()?->update(['to_stage' => MainDiagnosisStage::Draft]);

            return $main;
        });
    }

    /**
     * Before the invitation everything but the organization can change; after
     * it, until reading, only the time window, the peers and the quote
     * visibility (17 · 17.7).
     *
     * @param  array{starts_on?: ?string, publishes_on: string, show_peer_quotes: bool, excluded_peer_ids?: list<int>, name?: ?string}  $data
     */
    public function update(MainDiagnosis $main, array $data): void
    {
        if (! in_array($main->stage, [MainDiagnosisStage::Draft, MainDiagnosisStage::Invited, MainDiagnosisStage::Filling, MainDiagnosisStage::Running], true)) {
            throw new DiagnosisException(__('admin.main_diagnoses.locked_after_reading'));
        }

        $previousPublishing = $main->publishes_on?->toDateString();

        DB::transaction(function () use ($main, $data): void {
            $startsOn = filled($data['starts_on'] ?? null) ? $data['starts_on'] : null;

            $main->update([
                'starts_on' => $startsOn,
                'publishes_on' => $data['publishes_on'],
                'show_peer_quotes' => $data['show_peer_quotes'],
            ]);

            $main->diagnosis->update([
                ...(filled($data['name'] ?? null) ? ['name' => trim($data['name'])] : []),
                'diagnosed_on' => $startsOn ?? $main->diagnosis->diagnosed_on->toDateString(),
                'diagnosed_until' => $data['publishes_on'],
            ]);

            $added = $this->syncPeers($main, $data['excluded_peer_ids'] ?? []);

            // Peers added while the diagnosis runs get their invitation at once (25 · 25.1).
            if ($main->stage === MainDiagnosisStage::Running) {
                foreach ($added as $peer) {
                    $this->invitePeer($peer);
                }
            }
        });

        // A new publishing day is told to the leader (19 · 19.4).
        if ($previousPublishing !== $main->refresh()->publishes_on->toDateString()) {
            app(DiagnosisRunService::class)->notifyWindowChanged($main);
        }
    }

    /**
     * Why "Meghívó küldése" is greyed out, if it is (17 · 17.2).
     *
     * @return list<string>
     */
    public function invitationBlockers(MainDiagnosis $main): array
    {
        $organization = $main->diagnosis->organization;
        $blockers = [];

        if ($organization->contact_admin_id === null) {
            $blockers[] = __('admin.main_diagnoses.blocker_contact');
        }

        if ($main->subject !== MainDiagnosisSubject::Individual
            && ! $this->isOrganizationLeader($organization, $main->leader)
            && ($organization->valueSet()->first()?->items()->count() ?? 0) === 0) {
            $blockers[] = __('admin.main_diagnoses.blocker_values');
        }

        $membership = Membership::query()->whereBelongsTo($organization)->where('user_id', $main->leader_id)->first();

        if ($membership === null || $membership->status === MembershipStatus::Inactive) {
            $blockers[] = __('admin.main_diagnoses.blocker_leader_inactive');
        }

        return $blockers;
    }

    /**
     * "Meghívó küldése": the first gate; the leader's invitation goes out only from here.
     */
    public function invite(MainDiagnosis $main, User $actor): void
    {
        if ($blockers = $this->invitationBlockers($main)) {
            throw new DiagnosisException(implode(' ', $blockers));
        }

        DB::transaction(function () use ($main, $actor): void {
            $this->stages->transition($main, MainDiagnosisStage::Invited, $actor);
            $this->sendLeaderInvitation($main, $actor);
        });
    }

    /**
     * The leader is replaced with a confirmation; the old leader's answers
     * stay, the new one gets the invitation (17 · 17.7).
     */
    public function changeLeader(MainDiagnosis $main, User $newLeader, User $actor): void
    {
        if (! $main->acceptsInput() && $main->stage !== MainDiagnosisStage::Draft) {
            throw new DiagnosisException(__('admin.main_diagnoses.locked_after_reading'));
        }

        if ($main->leader_id === $newLeader->id) {
            return;
        }

        DB::transaction(function () use ($main, $newLeader, $actor): void {
            $main->participants()
                ->where('role', ParticipantRole::Leader)
                ->whereNull('replaced_at')
                ->update(['replaced_at' => now()]);

            // The new leader is no longer a peer of the same diagnosis.
            $main->participants()->where('role', ParticipantRole::Peer)->where('user_id', $newLeader->id)->update(['excluded_at' => now()]);

            $main->participants()->updateOrCreate(
                ['user_id' => $newLeader->id, 'role' => ParticipantRole::Leader],
                ['replaced_at' => null, 'excluded_at' => null, 'opened_at' => null],
            );
            $main->update(['leader_id' => $newLeader->id]);

            $diagnosis = $main->diagnosis;

            if ($main->subject === MainDiagnosisSubject::Team && $diagnosis->team !== null) {
                $diagnosis->team->users()->syncWithoutDetaching([$newLeader->id => ['is_leader' => true]]);
            }

            if ($main->subject === MainDiagnosisSubject::Person) {
                $diagnosis->update(['user_id' => $newLeader->id]);
            }

            if ($main->stage !== MainDiagnosisStage::Draft) {
                $this->sendLeaderInvitation($main->refresh(), $actor);
            }
        });

        // The leader counts as one person in the team experience (18 · 18.4).
        RecalculateMainDiagnosis::dispatch($main);
    }

    /**
     * "Lezárva": from any stage; closed before reading the status stays Draft (plan 3.6).
     */
    public function close(MainDiagnosis $main, User $actor): void
    {
        $this->stages->transition($main, MainDiagnosisStage::Closed, $actor);
    }

    /**
     * The first signed-in view of V1 or T1; the leader's moves the stage to
     * "Kitöltés" (17 · 17.5). Opening on someone's behalf does not count (plan 3.2).
     */
    public function opened(MainDiagnosis $main, MainDiagnosisParticipant $participant): void
    {
        if ($participant->opened_at === null) {
            $participant->update(['opened_at' => now()]);
            app(InboxService::class)->closeAutomatically('invitation:'.$participant->id);
        }

        if ($participant->role === ParticipantRole::Leader && $main->stage === MainDiagnosisStage::Invited) {
            $this->stages->transition($main, MainDiagnosisStage::Filling, $participant->loadMissing('user')->user);
        }
    }

    /**
     * "Indul a diagnózis" on V9 (23 · 23.4): every step done, the value set
     * version is recorded, the peers get their invitations.
     */
    public function start(MainDiagnosis $main, User $leader): void
    {
        if (! $main->isLeader($leader) || ! $main->stage->isBeforeRunning() || $main->stage === MainDiagnosisStage::Draft) {
            throw new DiagnosisException(__('diagnosis.cannot_start'));
        }

        $steps = $this->progress->forLeader($main, $leader);
        $missing = $steps->firstMissing(except: DiagnosisStep::Visibility);

        if ($missing !== null) {
            throw new DiagnosisException(__('diagnosis.step_missing', ['step' => $missing->step->label()]));
        }

        DB::transaction(function () use ($main, $leader): void {
            $valueSet = $this->valueSets->forOrganization($main->diagnosis->organization);
            $valueSet->recordVersion();

            $main->update(['value_set_version_id' => $valueSet->latestVersion()->first()?->id]);
            $this->stages->transition($main, MainDiagnosisStage::Running, $leader);

            foreach ($main->peers()->with('user')->get() as $peer) {
                $this->invitePeer($peer->setRelation('mainDiagnosis', $main));
            }
        });

        // The rows of the four blocks exist from the start (18 · 18.1).
        RecalculateMainDiagnosis::dispatch($main);
    }

    /**
     * A user put into the team while its diagnosis runs becomes a peer until
     * reading starts (17 · 17.2) and is invited at once if it already runs (25 · 25.1).
     */
    public function teamMemberAdded(Team $team, User $user): void
    {
        $mains = MainDiagnosis::query()
            ->acceptingInput()
            ->where('subject', MainDiagnosisSubject::Team)
            ->whereHas('diagnosis', fn ($query) => $query->where('team_id', $team->id))
            ->get();

        foreach ($mains as $main) {
            if (! $main->hasPeers() || $main->leader_id === $user->id) {
                continue;
            }

            $peer = $main->participants()->firstOrCreate(['user_id' => $user->id, 'role' => ParticipantRole::Peer]);

            if ($peer->excluded_at === null && $main->stage === MainDiagnosisStage::Running && $peer->invited_at === null) {
                $this->invitePeer($peer);
            }
        }
    }

    /**
     * The peer's e-mail: registration for a new account, the T1 link for an
     * existing one (25 · 25.1). Also the resend of 19 and 22.
     */
    public function invitePeer(MainDiagnosisParticipant $peer, ?User $actor = null): void
    {
        $main = $peer->mainDiagnosis;
        $organization = $main->diagnosis->organization;
        $membership = Membership::query()->whereBelongsTo($organization)->where('user_id', $peer->user_id)->first();

        if ($membership === null || $membership->status === MembershipStatus::Inactive) {
            return;
        }

        $token = $this->memberships->prepareInvitation($membership, $actor ?? $main->leader);
        $peer->update(['invited_at' => now()]);

        Mail::to($peer->user->email)->queue(new DiagnosisPeerInvitationMail($main, $peer->user, $token));
    }

    /**
     * "Új meghívó" from the Beérkező (22 · 22.2): a fresh link to the leader or the peer.
     */
    public function resendInvitation(MainDiagnosisParticipant $participant, User $actor): void
    {
        $main = $participant->mainDiagnosis;

        if ($participant->role === ParticipantRole::Leader) {
            $this->sendLeaderInvitation($main, $actor);

            return;
        }

        $this->invitePeer($participant, $actor);
    }

    /**
     * Main diagnoses the user takes part in that still take input (17 · 17.6).
     *
     * @return Collection<int, MainDiagnosis>
     */
    public function acceptingInputFor(User $user, Organization $organization): Collection
    {
        return MainDiagnosis::query()
            ->acceptingInput()
            ->withParticipant($user)
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $organization->id))
            ->with('diagnosis.team', 'diagnosis.organization', 'diagnosis.user')
            ->get()
            // A peer joins once the diagnosis runs and they started on T1.
            ->filter(fn (MainDiagnosis $main) => $main->isLeader($user)
                || ($main->stage === MainDiagnosisStage::Running && $main->participantFor($user, ParticipantRole::Peer)?->started_at !== null))
            ->values();
    }

    public function isOrganizationLeader(Organization $organization, User $user): bool
    {
        return Membership::query()
            ->whereBelongsTo($organization)
            ->where('user_id', $user->id)
            ->where('is_organization_leader', true)
            ->exists();
    }

    /**
     * Team members who can be peers: everyone but the leader, whatever their tags (17 · 17.2).
     *
     * @return Collection<int, User>
     */
    public function teamPeerCandidates(Team $team, ?int $leaderId): Collection
    {
        return $team->users()
            ->whereHas('memberships', fn ($query) => $query
                ->where('organization_id', $team->organization_id)
                ->where('status', '!=', MembershipStatus::Inactive))
            ->when($leaderId, fn ($query) => $query->whereKeyNot($leaderId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * "{Csapat} · {Termék} · {kezdés}" (17 · 17.1).
     */
    public function defaultName(Organization $organization, MainDiagnosisSubject $subject, ?Team $team, User $leader, string $productName, ?CarbonImmutable $startsOn): string
    {
        $subjectName = match ($subject) {
            MainDiagnosisSubject::Team => $team?->name,
            MainDiagnosisSubject::Person => $leader->name,
            MainDiagnosisSubject::Individual => $organization->name,
        };

        return mb_substr(implode(' · ', [$subjectName, $productName, ($startsOn ?? today())->format('Y.m.d.')]), 0, 255);
    }

    private function sendLeaderInvitation(MainDiagnosis $main, User $actor): void
    {
        $organization = $main->diagnosis->organization;
        $leader = $main->leader;
        $membership = Membership::query()->whereBelongsTo($organization)->where('user_id', $leader->id)->firstOrFail();
        $token = $this->memberships->prepareInvitation($membership, $actor);

        $main->leaderParticipant()?->update(['invited_at' => now()]);

        Mail::to($leader->email)->queue(new DiagnosisLeaderInvitationMail(
            $main,
            $leader,
            $actor,
            $token,
            $this->progress->forLeader($main, $leader)->totalMinutes(),
        ));
    }

    /**
     * @param  MainDiagnosisData  $data
     */
    private function resolveTeam(Organization $organization, array $data): Team
    {
        if (filled($data['new_team_name'] ?? null)) {
            return Team::create(['organization_id' => $organization->id, 'name' => trim($data['new_team_name'])]);
        }

        return Team::query()->whereBelongsTo($organization)->findOrFail($data['team_id'] ?? null);
    }

    /**
     * The leader of a team gets the "csapat vezető" tag there (17 · 17.2, 02 · 2.6);
     * a new leader is added with the 02 rules, without an e-mail yet.
     *
     * @param  MainDiagnosisData  $data
     */
    private function resolveLeader(Organization $organization, MainDiagnosisSubject $subject, ?Team $team, array $data, User $actor): User
    {
        if ($subject === MainDiagnosisSubject::Individual) {
            return $organization->users()->firstOrFail();
        }

        if ($subject === MainDiagnosisSubject::Team && filled($data['new_leader']['email'] ?? null)) {
            $membership = $this->memberships->add($organization, [
                'email' => $data['new_leader']['email'],
                'first_name' => $data['new_leader']['first_name'] ?? null,
                'last_name' => $data['new_leader']['last_name'] ?? null,
                'is_organization_leader' => false,
                'is_employee' => true,
                'is_partner' => false,
                'teams' => [],
            ], $actor, sendNow: false);
            $leader = $membership->user;
        } else {
            $leader = $organization->users()
                ->wherePivot('status', '!=', MembershipStatus::Inactive->value)
                ->findOrFail($data['leader_id'] ?? null);
        }

        if ($team !== null) {
            $team->users()->syncWithoutDetaching([$leader->id => ['is_leader' => true]]);
        }

        return $leader;
    }

    /**
     * Peers are the team's members except the leader; A1 can take any of them off.
     *
     * @param  list<int>  $excludedUserIds
     * @return list<MainDiagnosisParticipant> peers that are newly on the list
     */
    private function syncPeers(MainDiagnosis $main, array $excludedUserIds): array
    {
        if (! $main->hasPeers()) {
            return [];
        }

        $team = $main->diagnosis->team;
        $added = [];

        foreach ($this->teamPeerCandidates($team, $main->leader_id) as $user) {
            $peer = $main->participants()->firstOrCreate(['user_id' => $user->id, 'role' => ParticipantRole::Peer]);
            $wasOnList = ! $peer->wasRecentlyCreated && $peer->excluded_at === null;
            $exclude = in_array($user->id, $excludedUserIds, true);

            $peer->update(['excluded_at' => $exclude ? ($peer->excluded_at ?? now()) : null]);

            if (! $exclude && ! $wasOnList && $peer->invited_at === null) {
                $added[] = $peer;
            }
        }

        return $added;
    }
}
