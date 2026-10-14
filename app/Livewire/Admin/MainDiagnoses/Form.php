<?php

namespace App\Livewire\Admin\MainDiagnoses;

use App\Enums\DiagnosisStep;
use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Enums\MembershipStatus;
use App\Enums\ParticipantRole;
use App\Exceptions\DiagnosisException;
use App\Exceptions\HeadcountLimitReachedException;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\MainDiagnoses\MainDiagnosisService;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * A1 · starting a main diagnosis (17 · 17.2), with the live preview of what
 * the leader sees first (V1). After the invitation only the time window,
 * the peers and the quote visibility change; the leader can be replaced
 * with a confirmation (17 · 17.7).
 */
class Form extends Component
{
    public ?MainDiagnosis $main = null;

    #[Url(as: 'szervezet')]
    public ?int $organization_id = null;

    public ?int $contact_admin_id = null;

    public string $subject = MainDiagnosisSubject::Team->value;

    public ?int $team_id = null;

    public bool $newTeam = false;

    public string $new_team_name = '';

    public ?int $leader_id = null;

    public bool $newLeader = false;

    public string $leader_last_name = '';

    public string $leader_first_name = '';

    public string $leader_email = '';

    /**
     * Team members by user id: true while they are peers (17 · 17.2).
     *
     * @var array<int, bool>
     */
    public array $peers = [];

    public string $starts_on = '';

    public string $publishes_on = '';

    public bool $show_peer_quotes = true;

    public string $name = '';

    public ?int $replacementLeaderId = null;

    public function mount(?Diagnosis $diagnosis = null): void
    {
        if ($diagnosis?->exists) {
            Gate::authorize('update', $diagnosis);

            $main = $diagnosis->main;
            abort_if($main === null, 404);

            $this->main = $main;
            $this->organization_id = $diagnosis->organization_id;
            $this->contact_admin_id = $diagnosis->organization->contact_admin_id;
            $this->subject = $main->subject->value;
            $this->team_id = $diagnosis->team_id;
            $this->leader_id = $main->leader_id;
            $this->starts_on = (string) $main->starts_on?->toDateString();
            $this->publishes_on = $main->publishes_on->toDateString();
            $this->show_peer_quotes = $main->show_peer_quotes;
            $this->name = $diagnosis->name;
            $this->peers = $main->participants()
                ->where('role', ParticipantRole::Peer)
                ->pluck('excluded_at', 'user_id')
                ->map(fn ($excludedAt) => $excludedAt === null)
                ->all();

            // Team members who joined since are peers too (17 · 17.2).
            if ($main->hasPeers() && $diagnosis->team !== null) {
                foreach (app(MainDiagnosisService::class)->teamPeerCandidates($diagnosis->team, $main->leader_id) as $candidate) {
                    $this->peers[$candidate->id] ??= true;
                }
            }

            return;
        }

        Gate::authorize('create', Diagnosis::class);

        $this->starts_on = today()->addWeek()->toDateString();

        if ($this->organization_id !== null) {
            $this->updatedOrganizationId();
        }
    }

    public function updatedOrganizationId(): void
    {
        $organization = $this->organization();

        $this->reset(['team_id', 'leader_id', 'peers', 'newTeam', 'new_team_name', 'newLeader']);
        $this->contact_admin_id = $organization?->contact_admin_id;
        $this->subject = $organization?->isIndividual() ? MainDiagnosisSubject::Individual->value : MainDiagnosisSubject::Team->value;
        $this->publishes_on = $this->defaultPublishesOn();
    }

    public function updatedSubject(): void
    {
        $this->reset(['team_id', 'leader_id', 'peers', 'newLeader']);
    }

    public function updatedTeamId(): void
    {
        $this->resetPeers();
    }

    public function updatedLeaderId(): void
    {
        $this->resetPeers();
    }

    public function updatedStartsOn(): void
    {
        if ($this->main === null) {
            $this->publishes_on = $this->defaultPublishesOn();
        }
    }

    /**
     * "Mentés vázlatként": no e-mail goes out.
     */
    public function saveDraft(MainDiagnosisService $mainDiagnoses): void
    {
        $main = $this->createOrUpdate($mainDiagnoses);

        if ($main === null) {
            return;
        }

        session()->flash('status', __('admin.main_diagnoses.saved_draft'));
        $this->redirectRoute('admin.diagnoses.show', $main->diagnosis, navigate: false);
    }

    /**
     * "Meghívó küldése": the leader gets the e-mail, the stage becomes "Meghívva".
     */
    public function sendInvitation(MainDiagnosisService $mainDiagnoses): void
    {
        $main = $this->createOrUpdate($mainDiagnoses);

        if ($main === null) {
            return;
        }

        try {
            $mainDiagnoses->invite($main->refresh(), auth('admin')->user());
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());
            $this->redirectRoute('admin.main-diagnoses.edit', $main->diagnosis, navigate: false);

            return;
        }

        session()->flash('status', __('admin.main_diagnoses.invited', ['name' => $main->leader->name]));
        $this->redirectRoute('admin.diagnoses.show', $main->diagnosis, navigate: false);
    }

    public function changeLeader(MainDiagnosisService $mainDiagnoses): void
    {
        Gate::authorize('update', $this->main->diagnosis);

        $this->validate([
            'replacementLeaderId' => ['required', Rule::in($this->leaderOptions()->pluck('id')->reject(fn ($id) => $id === $this->main->leader_id))],
        ], attributes: ['replacementLeaderId' => __('admin.main_diagnoses.new_leader')]);

        try {
            $mainDiagnoses->changeLeader($this->main, User::query()->findOrFail($this->replacementLeaderId), auth('admin')->user());
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        session()->flash('status', __('admin.main_diagnoses.leader_changed'));
        $this->redirectRoute('admin.main-diagnoses.edit', $this->main->diagnosis, navigate: false);
    }

    public function close(MainDiagnosisService $mainDiagnoses): void
    {
        Gate::authorize('update', $this->main->diagnosis);

        try {
            $mainDiagnoses->close($this->main, auth('admin')->user());
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        session()->flash('status', __('admin.main_diagnoses.closed'));
        $this->redirectRoute('admin.diagnoses.show', $this->main->diagnosis, navigate: false);
    }

    public function render(MainDiagnosisService $mainDiagnoses): View
    {
        $organization = $this->organization();
        $product = $organization?->product;
        $subject = MainDiagnosisSubject::tryFrom($this->subject);
        $team = $this->team_id === null ? null : Team::query()->find($this->team_id);
        $leader = $this->leader();
        $hasFlow = $this->main !== null || ($product?->hasDiagnosisFlow() ?? false);
        $productKey = $this->main?->product_key ?? $product?->key;
        $settings = $productKey === null ? null : config('diagnosis.products.'.$productKey);
        $hasPeers = $subject === MainDiagnosisSubject::Team && ($settings['has_peers'] ?? false);
        $title = $this->main ? __('admin.main_diagnoses.edit_title') : __('admin.main_diagnoses.create_title');

        return view('livewire.admin.main-diagnoses.form', [
            'title' => $title,
            'organization' => $organization,
            'organizations' => Organization::query()->whereNull('deactivated_at')->orderBy('name')->get(['id', 'name', 'type']),
            'admins' => User::query()->admins()->whereNull('deactivated_at')->orderBy('last_name')->orderBy('first_name')->get(),
            'productName' => $this->main?->product_name ?? $product?->name,
            'hasFlow' => $hasFlow,
            'settings' => $settings,
            'hasPeers' => $hasPeers,
            'teams' => $organization?->teams()->orderBy('name')->get(['id', 'name']) ?? collect(),
            'leaderOptions' => $this->leaderOptions(),
            'peerCandidates' => $hasPeers && $team !== null ? $mainDiagnoses->teamPeerCandidates($team, $leader?->id) : collect(),
            'preview' => $this->preview($organization, $subject, $leader, $mainDiagnoses),
            'blockers' => $this->blockers($organization, $subject, $leader, $hasFlow, $mainDiagnoses),
            'readingStartsOn' => filled($this->publishes_on)
                ? rescue(fn () => today()->parse($this->publishes_on)->subDays((int) config('diagnosis.reading_offset_days'))->format('Y.m.d.'), null, false)
                : null,
            'canEditSettings' => $this->main === null || ! in_array($this->main->stage, [MainDiagnosisStage::Reading, MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true),
            'isDraft' => $this->main === null || $this->main->stage === MainDiagnosisStage::Draft,
        ])->layout('layouts::admin')->title($title);
    }

    private function createOrUpdate(MainDiagnosisService $mainDiagnoses): ?MainDiagnosis
    {
        if ($this->main !== null) {
            Gate::authorize('update', $this->main->diagnosis);

            $validated = $this->validate($this->windowRules(), attributes: $this->attributeNames());
            $this->updateContact();

            try {
                $mainDiagnoses->update($this->main, [
                    'name' => $validated['name'] ?? null,
                    'starts_on' => $validated['starts_on'] ?? null,
                    'publishes_on' => $validated['publishes_on'],
                    'show_peer_quotes' => $this->show_peer_quotes,
                    'excluded_peer_ids' => $this->excludedPeerIds(),
                ]);
            } catch (DiagnosisException $exception) {
                session()->flash('error', $exception->getMessage());

                return null;
            }

            return $this->main;
        }

        Gate::authorize('create', Diagnosis::class);

        $organization = $this->organization();

        $this->validate([
            'organization_id' => ['required', Rule::exists('organizations', 'id')->whereNull('deactivated_at')],
            'contact_admin_id' => ['nullable', Rule::exists('users', 'id')->whereNotNull('role')->whereNull('deactivated_at')],
            'subject' => ['required', Rule::in($organization?->isIndividual()
                ? [MainDiagnosisSubject::Individual->value]
                : [MainDiagnosisSubject::Team->value, MainDiagnosisSubject::Person->value])],
            'team_id' => [
                Rule::requiredIf($this->subject === MainDiagnosisSubject::Team->value && ! $this->newTeam),
                'nullable',
                Rule::exists('teams', 'id')->where('organization_id', $this->organization_id),
            ],
            'new_team_name' => [
                Rule::requiredIf($this->subject === MainDiagnosisSubject::Team->value && $this->newTeam),
                'nullable', 'string', 'max:255',
                Rule::unique('teams', 'name')->where('organization_id', $this->organization_id),
            ],
            'leader_id' => [
                Rule::requiredIf($this->subject !== MainDiagnosisSubject::Individual->value && ! $this->isNewLeader()),
                'nullable',
                Rule::in($this->leaderOptions()->pluck('id')),
            ],
            'leader_last_name' => [Rule::requiredIf($this->isNewLeader()), 'nullable', 'string', 'max:255'],
            'leader_first_name' => [Rule::requiredIf($this->isNewLeader()), 'nullable', 'string', 'max:255'],
            'leader_email' => [Rule::requiredIf($this->isNewLeader()), 'nullable', 'email', 'max:255', $this->notMemberYet()],
            ...$this->windowRules(),
        ], attributes: $this->attributeNames());

        if (! ($organization->product?->hasDiagnosisFlow() ?? false)) {
            $this->addError('organization_id', __('admin.main_diagnoses.no_flow'));

            return null;
        }

        $this->updateContact();

        try {
            return $mainDiagnoses->create([
                'organization_id' => $organization->id,
                'subject' => $this->subject,
                'team_id' => $this->newTeam ? null : $this->team_id,
                'new_team_name' => $this->newTeam ? $this->new_team_name : null,
                'leader_id' => $this->isNewLeader() ? null : $this->leader_id,
                'new_leader' => $this->isNewLeader() ? [
                    'first_name' => trim($this->leader_first_name),
                    'last_name' => trim($this->leader_last_name),
                    'email' => $this->leader_email,
                ] : null,
                'excluded_peer_ids' => $this->excludedPeerIds(),
                'starts_on' => filled($this->starts_on) ? $this->starts_on : null,
                'publishes_on' => $this->publishes_on,
                'show_peer_quotes' => $this->show_peer_quotes,
                'name' => $this->name,
            ], auth('admin')->user());
        } catch (HeadcountLimitReachedException) {
            $this->addError('leader_email', __('admin.main_diagnoses.headcount_full'));
        } catch (DiagnosisException $exception) {
            $this->addError('organization_id', $exception->getMessage());
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function windowRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['nullable', 'date'],
            'publishes_on' => ['required', 'date', 'after_or_equal:today', ...(filled($this->starts_on) ? ['after:starts_on'] : [])],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'organization_id' => __('admin.main_diagnoses.organization'),
            'contact_admin_id' => __('admin.main_diagnoses.contact'),
            'subject' => __('admin.main_diagnoses.subject'),
            'team_id' => __('admin.main_diagnoses.team'),
            'new_team_name' => __('admin.main_diagnoses.new_team'),
            'leader_id' => __('admin.main_diagnoses.leader'),
            'leader_last_name' => __('admin.members.last_name'),
            'leader_first_name' => __('admin.members.first_name'),
            'leader_email' => __('admin.members.email'),
            'name' => __('admin.main_diagnoses.name'),
            'starts_on' => __('admin.main_diagnoses.starts_on'),
            'publishes_on' => __('admin.main_diagnoses.publishes_on'),
        ];
    }

    /**
     * The contact person is the organization's (02 · 2.3, 19); A1 can set it.
     */
    private function updateContact(): void
    {
        $organization = $this->organization();

        if ($organization !== null && $organization->contact_admin_id !== $this->contact_admin_id) {
            Gate::authorize('update', $organization);
            $organization->update(['contact_admin_id' => $this->contact_admin_id]);
        }
    }

    private function notMemberYet(): Closure
    {
        return function (string $attribute, mixed $email, Closure $fail): void {
            $exists = Membership::query()
                ->where('organization_id', $this->organization_id)
                ->whereHas('user', fn ($query) => $query->where('email', Str::lower(trim((string) $email))))
                ->exists();

            if ($exists) {
                $fail(__('admin.main_diagnoses.leader_already_member'));
            }
        };
    }

    private function isNewLeader(): bool
    {
        return $this->main === null && $this->newLeader && $this->subject === MainDiagnosisSubject::Team->value;
    }

    /**
     * Users of the organization who can lead: not inactive (02).
     *
     * @return Collection<int, User>
     */
    private function leaderOptions(): Collection
    {
        if ($this->organization_id === null) {
            return collect();
        }

        return User::query()
            ->whereHas('memberships', fn ($query) => $query
                ->where('organization_id', $this->organization_id)
                ->where('status', '!=', MembershipStatus::Inactive))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function leader(): ?User
    {
        $organization = $this->organization();

        if ($organization?->isIndividual()) {
            return $organization->users()->first();
        }

        return $this->leader_id === null ? null : User::query()->find($this->leader_id);
    }

    private function organization(): ?Organization
    {
        return $this->organization_id === null ? null : Organization::query()->with('product')->find($this->organization_id);
    }

    /**
     * @return list<int>
     */
    private function excludedPeerIds(): array
    {
        return array_values(array_map('intval', array_keys(array_filter($this->peers, fn ($included) => ! $included))));
    }

    /**
     * Every team member but the leader is a peer until A1 takes them off (17 · 17.2).
     */
    private function resetPeers(): void
    {
        if ($this->main !== null) {
            return;
        }

        $team = $this->team_id === null ? null : Team::query()->find($this->team_id);

        $this->peers = $team === null ? [] : app(MainDiagnosisService::class)
            ->teamPeerCandidates($team, $this->leader_id)
            ->mapWithKeys(fn (User $user) => [$user->id => true])
            ->all();
    }

    private function defaultPublishesOn(): string
    {
        $days = config('diagnosis.products.'.($this->organization()?->product?->key ?? '').'.days', 42);
        $start = filled($this->starts_on) ? rescue(fn () => today()->parse($this->starts_on), today(), false) : today();

        return $start->addDays((int) $days)->toDateString();
    }

    /**
     * What the leader sees on first entry (V1), narrowed by product and subject (17 · 17.3).
     *
     * @return list<array{label: string, minutes: int, readonly: bool}>
     */
    private function preview(?Organization $organization, ?MainDiagnosisSubject $subject, ?User $leader, MainDiagnosisService $mainDiagnoses): array
    {
        if ($organization === null || $subject === null) {
            return [];
        }

        $isOrganizationLeader = $subject === MainDiagnosisSubject::Individual
            || ($leader !== null && $mainDiagnoses->isOrganizationLeader($organization, $leader));

        return array_map(function (string $key) use ($isOrganizationLeader): array {
            $step = DiagnosisStep::from($key);
            $readonly = ! $isOrganizationLeader && in_array($step, [DiagnosisStep::OrganizationValues, DiagnosisStep::Brand], true);

            return [
                'label' => $step->label(),
                'minutes' => $readonly ? (int) config('diagnosis.readonly_step_minutes') : $step->minutes(),
                'readonly' => $readonly,
            ];
        }, config('diagnosis.steps.'.$subject->value));
    }

    /**
     * Why "Meghívó küldése" is greyed out (17 · 17.2).
     *
     * @return list<string>
     */
    private function blockers(?Organization $organization, ?MainDiagnosisSubject $subject, ?User $leader, bool $hasFlow, MainDiagnosisService $mainDiagnoses): array
    {
        if ($this->main !== null) {
            return $this->main->stage === MainDiagnosisStage::Draft ? $mainDiagnoses->invitationBlockers($this->main) : [];
        }

        if ($organization === null) {
            return [];
        }

        $blockers = [];

        if (! $hasFlow) {
            $blockers[] = __('admin.main_diagnoses.no_flow');
        }

        if ($this->contact_admin_id === null) {
            $blockers[] = __('admin.main_diagnoses.blocker_contact');
        }

        if ($subject !== MainDiagnosisSubject::Individual
            && ($leader === null || ! $mainDiagnoses->isOrganizationLeader($organization, $leader))
            && ($organization->valueSet()->first()?->items()->count() ?? 0) === 0) {
            $blockers[] = __('admin.main_diagnoses.blocker_values');
        }

        return $blockers;
    }
}
