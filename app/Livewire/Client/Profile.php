<?php

namespace App\Livewire\Client;

use App\Enums\AuditEventType;
use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Enums\TokenPurpose;
use App\Exceptions\DiagnosisException;
use App\Mail\PasswordChangedMail;
use App\Mail\PasswordConfirmationCodeMail;
use App\Models\CalendarConnection;
use App\Models\Consent;
use App\Models\LegalAcceptance;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\User;
use App\Services\Accounts\AccountDeletionService;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\GuardSession;
use App\Services\Auth\ImpersonationService;
use App\Services\Auth\TokenService;
use App\Services\Calendar\CalendarService;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\MainDiagnoses\ConsentService;
use App\Services\MainDiagnoses\DiagnosisRunService;
use App\Services\Materials\MaterialStore;
use App\Services\Report\ReportService;
use App\Support\PasswordRules;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Profil (31, first part): name and password (14 · 14.3), and the own
 * recordings (31.5). The diagnosis, consent, calendar and deletion blocks
 * come with their tickets.
 */
class Profile extends Component
{
    public Organization $organization;

    public string $last_name = '';

    public string $first_name = '';

    public bool $nameSaved = false;

    public string $current_password = '';

    public string $code = '';

    public bool $codeSent = false;

    public string $password = '';

    public string $password_confirmation = '';

    public bool $passwordSaved = false;

    /** "Adataim törlése" (31 · 31.7): confirmed with the password or a fresh code. */
    public bool $confirmingDeletion = false;

    public string $deletionPassword = '';

    public string $deletionCode = '';

    public bool $deletionCodeSent = false;

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;

        $user = auth()->user();
        $this->last_name = (string) $user->last_name;
        $this->first_name = (string) $user->first_name;
    }

    /**
     * Fields save when left; a short "Mentve" confirms it (15 · 15.8).
     */
    public function updated(string $property): void
    {
        if (! in_array($property, ['last_name', 'first_name'], true)) {
            return;
        }

        $validated = $this->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
        ]);

        auth()->user()->fill($validated)->save();
        $this->nameSaved = true;
    }

    /**
     * Without a password, a fresh code confirms that it is really the owner (14 · 14.3).
     */
    public function sendCode(TokenService $tokens, ImpersonationService $impersonation): void
    {
        abort_if($impersonation->isImpersonating(), 403, __('impersonation.forbidden'));

        $user = auth()->user();
        $issued = $tokens->issue($user, TokenPurpose::PasswordConfirmationCode);

        Mail::to($user->email)->queue(new PasswordConfirmationCodeMail($user, (string) $issued->plainCode));
        $this->codeSent = true;
    }

    public function savePassword(TokenService $tokens, GuardSession $guardSession, ImpersonationService $impersonation): void
    {
        abort_if($impersonation->isImpersonating(), 403, __('impersonation.forbidden'));

        $user = auth()->user();
        $hasPassword = $user->password !== null;

        $this->validate([
            'current_password' => $hasPassword ? ['required', 'string', 'current_password:'.GuardSession::WEB] : [],
            'code' => $hasPassword ? [] : ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', PasswordRules::for($user)],
        ]);

        if (! $hasPassword) {
            $token = $tokens->pendingFor($user, TokenPurpose::PasswordConfirmationCode);

            if ($token === null || ! $tokens->checkCode($token, $this->code)) {
                throw ValidationException::withMessages(['code' => __('client.profile.code_invalid')]);
            }

            $tokens->consume($token);
        }

        $user->forceFill(['password' => $this->password])->save();
        $user->invalidateSessions();
        $guardSession->rememberEpoch(GuardSession::WEB, $user->refresh());

        Mail::to($user->email)->queue(new PasswordChangedMail($user, now()->toImmutable()));

        $this->reset(['current_password', 'code', 'codeSent', 'password', 'password_confirmation']);
        $this->passwordSaved = true;
    }

    /**
     * Felvételeim (31 · 31.5): only the audio goes, the transcript and the
     * diagnosis stay (06 · 6.6). Not in the user's name (14 · 14.6).
     */
    public function deleteRecording(string $uuid, MaterialStore $materials, ImpersonationService $impersonation): void
    {
        abort_if($impersonation->isImpersonating(), 403, __('impersonation.forbidden'));

        $material = Material::query()->where('uuid', $uuid)->where('user_id', auth()->id())->firstOrFail();
        $materials->deleteAudio($material);
        app(AuditLogger::class)->record(AuditEventType::RecordingDeleted, auth()->user(), auth()->user(), ['date' => $material->created_at->format('Y.m.d.')]);

        session()->flash('status', __('client.recordings.deleted'));
    }

    /**
     * Hozzájárulásaim (31 · 31.6): until publishing; never in the user's name (14 · 14.6).
     */
    public function revokeConsent(int $consentId, ConsentService $consents, ImpersonationService $impersonation): void
    {
        abort_if($impersonation->isImpersonating(), 403, __('impersonation.forbidden'));

        $consent = Consent::query()->where('user_id', auth()->id())->findOrFail($consentId);

        try {
            $consents->revoke($consent, auth()->user());
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        session()->flash('status', __('client.consents.revoked'));
    }

    /**
     * "Szétkötés" (09 · 9.5, 31 · 31.5): the metadata goes at once.
     */
    public function disconnectCalendar(int $connectionId, CalendarService $calendars): void
    {
        $connection = CalendarConnection::query()->where('user_id', auth()->id())->findOrFail($connectionId);
        $calendars->disconnect($connection);

        session()->flash('status', __('calendar.profile.disconnected'));
    }

    /**
     * "Emlékeztető" from Emberek (31 · 31.4), the rule of 26 · 26.1.
     */
    public function remindPeers(int $mainId, DiagnosisRunService $run, ImpersonationService $impersonation): void
    {
        abort_if($impersonation->isImpersonating(), 403, __('impersonation.forbidden'));
        $main = MainDiagnosis::query()->whereKey($mainId)->where('leader_id', auth()->id())->where('stage', MainDiagnosisStage::Running)->firstOrFail();

        $run->remindPeers($main, auth()->user());
        session()->flash('status', __('diagnosis.home.reminded'));
    }

    public function sendDeletionCode(TokenService $tokens, ImpersonationService $impersonation): void
    {
        abort_if($impersonation->isImpersonating(), 403, __('impersonation.forbidden'));

        $user = auth()->user();
        $issued = $tokens->issue($user, TokenPurpose::PasswordConfirmationCode);

        Mail::to($user->email)->queue(new PasswordConfirmationCodeMail($user, (string) $issued->plainCode));
        $this->deletionCodeSent = true;
    }

    /**
     * "Adataim törlése" (31 · 31.7): inactive and signed out at once, the data
     * goes within 24 hours. Never in the user's name (14 · 14.6).
     */
    public function deleteAccount(AccountDeletionService $deletions, TokenService $tokens, ImpersonationService $impersonation): void
    {
        abort_if($impersonation->isImpersonating(), 403, __('impersonation.forbidden'));

        $user = auth()->user();
        $hasPassword = $user->password !== null;

        $this->validate([
            'deletionPassword' => $hasPassword ? ['required', 'string', 'current_password:'.GuardSession::WEB] : [],
            'deletionCode' => $hasPassword ? [] : ['required', 'string'],
        ], attributes: ['deletionPassword' => __('client.profile.current_password'), 'deletionCode' => __('client.profile.code')]);

        if (! $hasPassword) {
            $token = $tokens->pendingFor($user, TokenPurpose::PasswordConfirmationCode);

            if ($token === null || ! $tokens->checkCode($token, $this->deletionCode)) {
                throw ValidationException::withMessages(['deletionCode' => __('client.profile.code_invalid')]);
            }

            $tokens->consume($token);
        }

        $deletions->request($user);
        auth('web')->logout();
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('status', __('client.deletion.requested'));

        $this->redirect(route('client.login'), navigate: false);
    }

    public function render(): View
    {
        $user = auth()->user();
        $membership = $user->memberships()->where('organization_id', $this->organization->id)->first();
        $teams = $user->teams()->where('teams.organization_id', $this->organization->id)->pluck('name');

        $leaderMain = MainDiagnosis::query()
            ->where('leader_id', $user->id)
            ->whereNotIn('stage', [MainDiagnosisStage::Draft->value, MainDiagnosisStage::Closed->value])
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $this->organization->id))
            ->with('diagnosis.organization.contactAdmin', 'diagnosis.team')
            ->latest('id')
            ->first();

        return view('livewire.client.profile', [
            'leaderMain' => $leaderMain,
            'stateLine' => $leaderMain === null ? null : $this->stateLine($leaderMain),
            'people' => $leaderMain !== null && $leaderMain->hasPeers() ? [
                'total' => $leaderMain->peers()->count(),
                'answered' => app(ThresholdPolicy::class)->peersAnswered($leaderMain),
                'can_remind' => $leaderMain->stage === MainDiagnosisStage::Running,
            ] : null,
            'awaiting' => $this->awaiting($user),
            'user' => $user,
            'roleLine' => collect($membership?->tagLabels() ?? [])->merge($teams)->implode(' · '),
            'hasPassword' => $user->password !== null,
            'isImpersonating' => app(ImpersonationService::class)->isImpersonating(),
            'recordings' => Material::query()
                ->where('user_id', $user->id)
                ->whereNotNull('audio_path')
                ->whereNull('audio_deleted_at')
                ->latest('id')
                ->get(),
            'acceptances' => LegalAcceptance::query()
                ->where('user_id', $user->id)
                ->with('version')
                ->latest('accepted_at')
                ->get(),
            'consents' => Consent::query()
                ->where('user_id', $user->id)
                ->with('mainDiagnosis.diagnosis.team', 'mainDiagnosis.diagnosis.organization', 'mainDiagnosis.diagnosis.user')
                ->latest('id')
                ->get()
                ->groupBy('main_diagnosis_id'),
            'consentService' => app(ConsentService::class),
            'calendars' => CalendarConnection::query()
                ->where('user_id', $user->id)
                ->where('organization_id', $this->organization->id)
                ->where('status', '!=', CalendarConnection::DISCONNECTED)
                ->latest('id')
                ->get(),
        ])->layout('layouts::client', [
            'title' => __('client.profile.title'),
            'color' => 'white',
            'back' => route('client.home', $this->organization),
        ]);
    }

    /**
     * "fut · 3. hét a 7-ből", "publikálva · 04.18." (31 · 31.2).
     */
    private function stateLine(MainDiagnosis $main): string
    {
        if ($main->stage === MainDiagnosisStage::Published) {
            return __('client.profile.state_published', ['date' => $main->published_at?->format('m.d.') ?? '–']);
        }

        $start = ($main->running_at ?? $main->starts_on ?? now())->copy()->startOfDay();
        $weeks = max(1, (int) ceil(($start->diffInDays($main->publishes_on) + 1) / 7));
        $week = min($weeks, max(1, (int) floor($start->diffInDays(now()) / 7) + 1));

        return $main->stage === MainDiagnosisStage::Running
            ? __('client.profile.state_running', ['week' => $week, 'weeks' => $weeks])
            : mb_strtolower($main->stage->label());
    }

    /**
     * Rád vár (31 · 31.3): at most three rows from the active organization, each to its place.
     *
     * @return list<array{label: string, url: string}>
     */
    private function awaiting(User $user): array
    {
        $rows = [];
        $mains = MainDiagnosis::query()
            ->withParticipant($user)
            ->whereNotIn('stage', [MainDiagnosisStage::Draft->value, MainDiagnosisStage::Closed->value])
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $this->organization->id))
            ->with('diagnosis')
            ->latest('id')
            ->get();

        foreach ($mains as $main) {
            $isLeader = $main->isLeader($user);

            if ($main->stage === MainDiagnosisStage::Published && $main->published_at?->isAfter(now()->subDays(14)) && app(ReportService::class)->canView($user, $main)) {
                $rows[] = ['label' => __('client.profile.awaiting.report', ['product' => $main->product_name, 'date' => $main->published_at->format('m.d.')]), 'url' => route('client.report.show', [$this->organization, $main->diagnosis])];
            }

            if ($isLeader && $main->stage === MainDiagnosisStage::Running && $main->hasPeers()) {
                $missing = $main->peers()->count() - app(ThresholdPolicy::class)->peersAnswered($main);

                if ($missing > 0) {
                    $rows[] = ['label' => __('client.profile.awaiting.peers', ['count' => $missing]), 'url' => route('client.diagnosis.show', [$this->organization, $main->diagnosis])];
                }
            }

            if ($main->acceptsInput()) {
                $open = Material::query()
                    ->where('parent_diagnosis_id', $main->diagnosis_id)
                    ->where('user_id', $user->id)
                    ->whereNotNull('process_occasion_id')
                    ->whereDoesntHave('occasion.experiences', fn ($query) => $query->where('user_id', $user->id))
                    ->with('occasion.process')
                    ->first();

                if ($open !== null) {
                    $rows[] = ['label' => __('client.profile.awaiting.experience', ['process' => $open->occasion->process->name, 'date' => $open->occasion->occurred_at->format('m.d.')]), 'url' => route('client.diagnosis.occasion-experience', [$this->organization, $main->diagnosis, $open])];
                }
            }

            $peer = $main->participantFor($user, ParticipantRole::Peer);

            if ($peer !== null && $peer->started_at === null && $main->stage === MainDiagnosisStage::Running) {
                $rows[] = ['label' => __('client.profile.awaiting.peer', ['subject' => $main->subjectName()]), 'url' => route('client.diagnosis.show', [$this->organization, $main->diagnosis])];
            }
        }

        return array_slice($rows, 0, 3);
    }
}
