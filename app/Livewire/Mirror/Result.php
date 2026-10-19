<?php

namespace App\Livewire\Mirror;

use App\Enums\LegalDocumentType;
use App\Enums\MirrorStatus;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\User;
use App\Models\Value;
use App\Rules\Turnstile;
use App\Services\Auth\CustomerAuthenticator;
use App\Services\Auth\LandingResolver;
use App\Services\Auth\LoginCodeService;
use App\Services\Auth\LoginOutcome;
use App\Services\Legal\LegalDocuments;
use App\Services\Mirror\MirrorService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * ET3 · the values with quotes, then ET5 · the full picture with the
 * curiosity questions and the account (06 · 6.1). From the own menu (6.2)
 * there is no e-mail and no account step.
 */
class Result extends Component
{
    public MirrorSession $session;

    public ?Organization $organization = null;

    public string $step = 'values';

    public string $email = '';

    public bool $consent = false;

    /** @var list<string> */
    public array $curiosity = [];

    public string $turnstileToken = '';

    public string $loginMode = 'password';

    public string $password = '';

    public string $code = '';

    public bool $codeSent = false;

    public bool $retryLimited = false;

    public function mount(MirrorSession $session, ?Organization $organization = null): void
    {
        $this->organization = $organization?->exists ? $organization : null;

        abort_unless($this->canSee($session), 404);

        $this->session = $session;
        $this->curiosity = $session->curiosity ?? [];
        $this->email = (string) $session->email;

        if ($session->status === MirrorStatus::AwaitingConfirmation) {
            $this->step = 'sent';
        }
    }

    public function toggle(string $key, MirrorService $mirrors): void
    {
        $this->ensureOwner();

        $mirrors->toggle($this->session, $key);
    }

    /**
     * "Újra próbálom" goes again from what was sent; every try calls the AI,
     * so a mirror gets three in an hour.
     */
    public function retry(MirrorService $mirrors): void
    {
        $this->ensureOwner();
        abort_unless($mirrors->canRetry($this->session), 403);

        $key = 'mirror-retry:'.$this->session->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->retryLimited = true;

            return;
        }

        RateLimiter::hit($key, 3600);
        $mirrors->retry($this->session);
    }

    public function showFull(): void
    {
        $this->step = 'full';
    }

    public function backToValues(): void
    {
        $this->step = 'values';
    }

    /**
     * "Létrehozom a fiókot" and "Időpontot foglalok" (06 · 6.1, ET5).
     */
    public function createAccount(bool $booking, MirrorService $mirrors): void
    {
        abort_if($this->organization !== null || ! $this->session->isAnonymous(), 403);

        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'consent' => ['accepted'],
            'curiosity' => ['array'],
            'curiosity.*' => [Rule::in(MirrorSession::CURIOSITY)],
            'turnstileToken' => [new Turnstile],
        ], ['consent.accepted' => __('mirror.consent_required')], ['email' => __('mirror.email')]);
        $this->turnstileToken = '';

        $result = $mirrors->requestAccount($this->session, $this->email, $this->curiosity, $booking);

        $this->step = $result === MirrorService::EXISTING_ACCOUNT ? 'existing' : 'sent';
    }

    /**
     * An existing account signs in on the spot, so the mirror is not lost (06 · 6.1).
     */
    public function loginWithPassword(CustomerAuthenticator $authenticator, MirrorService $mirrors, LandingResolver $landing): void
    {
        $this->validate(['password' => ['required', 'string'], 'turnstileToken' => [new Turnstile]], attributes: ['password' => __('mirror.password')]);
        $this->turnstileToken = '';
        $this->throttleLogin();

        [$outcome, $user] = $authenticator->attemptPassword($this->session->email ?? $this->email, $this->password);

        if ($outcome !== LoginOutcome::Success || $user === null) {
            throw ValidationException::withMessages(['password' => __('mirror.login_failed')]);
        }

        $this->signIn($user, $authenticator, $mirrors, $landing);
    }

    public function sendCode(LoginCodeService $codes): void
    {
        $this->validate(['turnstileToken' => [new Turnstile]]);
        $this->turnstileToken = '';
        $this->throttleLogin();

        $codes->send($this->session->email ?? $this->email);
        $this->codeSent = true;
    }

    public function loginWithCode(LoginCodeService $codes, CustomerAuthenticator $authenticator, MirrorService $mirrors, LandingResolver $landing): void
    {
        $this->validate(['code' => ['required', 'string', 'size:6']], attributes: ['code' => __('mirror.code')]);
        $this->throttleLogin();

        $user = $codes->verifyCode($this->session->email ?? $this->email, $this->code);

        if ($user === null) {
            throw ValidationException::withMessages(['code' => __('mirror.code_invalid')]);
        }

        $this->signIn($user, $authenticator, $mirrors, $landing);
    }

    /**
     * Own menu: the ticked questions are kept, the diagnosis is already there (06 · 6.2).
     */
    public function finish(): void
    {
        $this->ensureOwner();
        $this->saveCuriosity();

        $parent = $this->session->material?->parent_diagnosis_id;

        // A mirror told for the own values goes back there with its values (24 · 24.1).
        if ($parent !== null && $this->organization !== null) {
            session()->flash('status', __('mirror.back_to_diagnosis'));
            $this->redirect(route('client.diagnosis.sajat-ertekek', [$this->organization, $parent]), navigate: false);

            return;
        }

        session()->flash('status', __('mirror.saved_to', ['organization' => $this->organization?->name]));
        $this->redirect(route('client.values', $this->organization), navigate: false);
    }

    public function book(MirrorService $mirrors): void
    {
        $this->ensureOwner();
        $this->saveCuriosity();
        $mirrors->recordBooking($this->session->fresh());

        session()->flash('status', __('mirror.booking_saved'));
    }

    public function render(MirrorService $mirrors, LegalDocuments $legalDocuments): View
    {
        $this->session->refresh();
        $groups = $mirrors->groups($this->session);
        $kept = $groups->where('excluded', false);

        $view = view('livewire.mirror.result', [
            'groups' => $groups,
            'keptGroups' => $kept,
            'isPublic' => $this->organization === null,
            'isExpired' => $this->session->isAnonymous() && $this->session->isExpired(),
            'canRetry' => $mirrors->canRetry($this->session),
            'otherValues' => max(0, Value::query()->availableTo(null)->count() - $kept->whereNotNull('valueId')->count()),
            'bookingUrl' => config('megis.booking_url'),
            'termsUrl' => $legalDocuments->current(LegalDocumentType::Terms) ? route('legal.show', LegalDocumentType::Terms->value) : null,
            'privacyUrl' => $legalDocuments->current(LegalDocumentType::PrivacyPolicy) ? route('legal.show', LegalDocumentType::PrivacyPolicy->value) : null,
        ]);

        return $this->organization === null
            ? $view->layout('layouts::public', ['title' => __('mirror.values_title'), 'noindex' => true])
            : $view->layout('layouts::client', ['title' => __('mirror.title'), 'back' => route('client.mirror.record', $this->organization), 'backLabel' => __('mirror.title')]);
    }

    /**
     * A claimed mirror is its owner's; an anonymous one only opens in the
     * browser that made it.
     */
    private function canSee(MirrorSession $session): bool
    {
        if ($session->user_id !== null) {
            return auth('web')->id() === $session->user_id
                && ($this->organization === null || $session->organization_id === $this->organization->id);
        }

        return $this->organization === null && in_array($session->token, session('mirror_tokens', []), true);
    }

    private function ensureOwner(): void
    {
        abort_unless($this->canSee($this->session), 403);
    }

    private function saveCuriosity(): void
    {
        $this->validate(['curiosity' => ['array'], 'curiosity.*' => [Rule::in(MirrorSession::CURIOSITY)]]);
        $this->session->update(['curiosity' => array_values($this->curiosity)]);
    }

    private function throttleLogin(): void
    {
        $key = 'mirror-login:'.mb_strtolower((string) $this->session->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['password' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        RateLimiter::hit($key, 60);
    }

    private function signIn(User $user, CustomerAuthenticator $authenticator, MirrorService $mirrors, LandingResolver $landing): void
    {
        $authenticator->login($user);
        $mirrors->attachToExisting($this->session, $user);

        $this->redirect($landing->urlFor($user), navigate: false);
    }
}
