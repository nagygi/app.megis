<?php

namespace App\Livewire\Client;

use App\Enums\TokenPurpose;
use App\Exceptions\DiagnosisException;
use App\Mail\PasswordChangedMail;
use App\Mail\PasswordConfirmationCodeMail;
use App\Models\CalendarConnection;
use App\Models\Consent;
use App\Models\LegalAcceptance;
use App\Models\Material;
use App\Models\Organization;
use App\Services\Auth\GuardSession;
use App\Services\Auth\ImpersonationService;
use App\Services\Auth\TokenService;
use App\Services\Calendar\CalendarService;
use App\Services\MainDiagnoses\ConsentService;
use App\Services\Materials\MaterialStore;
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

    public function render(): View
    {
        $user = auth()->user();
        $membership = $user->memberships()->where('organization_id', $this->organization->id)->first();
        $teams = $user->teams()->where('teams.organization_id', $this->organization->id)->pluck('name');

        return view('livewire.client.profile', [
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
}
