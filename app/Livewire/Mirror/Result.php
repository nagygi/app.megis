<?php

namespace App\Livewire\Mirror;

use App\Enums\MirrorInsightStatus;
use App\Livewire\Concerns\ChecksTurnstile;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\User;
use App\Models\Value;
use App\Services\Auth\CustomerAuthenticator;
use App\Services\Auth\LoginCodeService;
use App\Services\Auth\LoginOutcome;
use App\Services\Mirror\MirrorInsightService;
use App\Services\Mirror\MirrorService;
use App\Services\Mirror\MirrorValueGroup;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * ET3 · the values with quotes, ET4 · one value in the user's own words
 * (NM-06), ET5 · the full picture with the curiosity questions (06 · 6.1).
 * The e-mail came with the recording: at the end a new address is asked to
 * confirm it, an existing account signs in. From the own menu (6.2) there is
 * no account step.
 */
class Result extends Component
{
    use ChecksTurnstile;

    public const STEPS = ['values', 'value', 'full'];

    public MirrorSession $session;

    public ?Organization $organization = null;

    #[Url(as: 'lap', history: true)]
    public string $step = 'values';

    /**
     * ET4: the kept value on the page, from 1.
     */
    #[Url(as: 'ertek', history: true)]
    public int $position = 1;

    /** @var list<string> */
    public array $curiosity = [];

    public bool $rewriting = false;

    public string $meaning = '';

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

        if (! in_array($this->step, self::STEPS, true)) {
            $this->step = 'values';
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

    public function showValues(): void
    {
        $this->step = 'values';
    }

    /**
     * ET4, the n-th kept value.
     */
    public function showValue(int $position): void
    {
        $this->step = 'value';
        $this->position = max(1, $position);
        $this->rewriting = false;
        $this->resetValidation();
    }

    public function showFull(): void
    {
        $this->step = 'full';
    }

    /**
     * "Ez az": the Coach's sentence is the user's meaning (NM-06).
     */
    public function accept(MirrorService $mirrors, MirrorInsightService $insights): void
    {
        $this->ensureOwner();
        $group = $this->currentGroup($mirrors);

        if ($group !== null) {
            $insights->accept($this->session, $group->key);
        }
    }

    /**
     * "Átírom": the user's own sentence instead.
     */
    public function startRewrite(MirrorService $mirrors, MirrorInsightService $insights): void
    {
        $this->ensureOwner();
        $group = $this->currentGroup($mirrors);

        $this->meaning = $group === null ? '' : (string) $insights->forSession($this->session)->get($group->key)?->meaning;
        $this->rewriting = true;
    }

    public function cancelRewrite(): void
    {
        $this->rewriting = false;
        $this->resetValidation();
    }

    public function saveRewrite(MirrorService $mirrors, MirrorInsightService $insights): void
    {
        $this->ensureOwner();
        $this->validate(
            ['meaning' => ['required', 'string', 'max:'.MirrorInsightService::MEANING_MAX]],
            attributes: ['meaning' => __('mirror.coach_rewrite_label')],
        );

        $group = $this->currentGroup($mirrors);

        if ($group !== null) {
            $insights->rewrite($this->session, $group->key, $this->meaning);
        }

        $this->rewriting = false;
    }

    /**
     * A ticked question is kept at once; there is no button for it (ET5).
     */
    public function updatedCuriosity(): void
    {
        $this->ensureOwner();
        $this->saveCuriosity();
    }

    /**
     * "Elküldöm": the ticked questions go to the mégis by e-mail, once a mirror.
     */
    public function sendInterest(MirrorService $mirrors): void
    {
        $this->ensureOwner();

        if ($this->session->interest_sent_at !== null) {
            return;
        }

        if ($this->curiosity === []) {
            throw ValidationException::withMessages(['curiosity' => __('mirror.interest_required')]);
        }

        $this->saveCuriosity();
        $mirrors->sendInterest($this->session);
    }

    /**
     * An existing account signs in on the spot, so the mirror is not lost (06 · 6.1).
     */
    public function loginWithPassword(CustomerAuthenticator $authenticator, MirrorService $mirrors): void
    {
        abort_unless($this->session->belongsToExistingAccount(), 403);
        $this->validate(['password' => ['required', 'string']], attributes: ['password' => __('mirror.password')]);
        $this->checkTurnstile();
        $this->throttleLogin();

        [$outcome, $user] = $authenticator->attemptPassword((string) $this->session->email, $this->password);

        if ($outcome !== LoginOutcome::Success || $user === null) {
            throw ValidationException::withMessages(['password' => __('mirror.login_failed')]);
        }

        $this->signIn($user, $authenticator, $mirrors);
    }

    public function sendCode(LoginCodeService $codes): void
    {
        abort_unless($this->session->belongsToExistingAccount(), 403);
        $this->checkTurnstile();
        $this->throttleLogin();

        $codes->send((string) $this->session->email);
        $this->codeSent = true;
    }

    public function loginWithCode(LoginCodeService $codes, CustomerAuthenticator $authenticator, MirrorService $mirrors): void
    {
        abort_unless($this->session->belongsToExistingAccount(), 403);
        $this->validate(['code' => ['required', 'string', 'size:6']], attributes: ['code' => __('mirror.code')]);
        $this->throttleLogin();

        $user = $codes->verifyCode((string) $this->session->email, $this->code);

        if ($user === null) {
            throw ValidationException::withMessages(['code' => __('mirror.code_invalid')]);
        }

        $this->signIn($user, $authenticator, $mirrors);
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

    public function render(MirrorService $mirrors, MirrorInsightService $insights): View
    {
        $this->session->refresh();
        $groups = $mirrors->groups($this->session);
        $kept = $groups->where('excluded', false)->values();

        // A value taken out on ET3 leaves ET4 for the list.
        if ($this->step === 'value' && $kept->isEmpty()) {
            $this->step = 'values';
        }

        $this->position = min(max(1, $this->position), max(1, $kept->count()));
        $current = $this->step === 'value' ? $kept->get($this->position - 1) : null;

        $view = view('livewire.mirror.result', [
            'groups' => $groups,
            'keptGroups' => $kept,
            'current' => $current,
            'next' => $current === null ? null : $kept->get($this->position),
            'insight' => $current === null ? null : $insights->forSession($this->session)->get($current->key),
            'insightPending' => $this->session->insight_status === MirrorInsightStatus::Pending,
            'libraryValue' => $current?->valueId === null ? null : Value::query()->find($current->valueId),
            'fromRecording' => (bool) $this->session->material?->source->hasAudio(),
            'isPublic' => $this->organization === null,
            'isExpired' => $this->session->isAnonymous() && $this->session->isExpired(),
            'canRetry' => $mirrors->canRetry($this->session),
            'otherValues' => max(0, Value::query()->availableTo(null)->count() - $kept->whereNotNull('valueId')->count()),
            'accountUrl' => $this->accountUrl(),
            'interestSent' => $this->session->interest_sent_at !== null,
            'interestEmail' => $this->session->user?->email ?? $this->session->email,
        ]);

        return $this->organization === null
            ? $view->layout('layouts::public', ['title' => __('mirror.values_title'), 'noindex' => true])
            : $view->layout('layouts::client', ['title' => __('mirror.title'), 'back' => route('client.mirror.record', $this->organization), 'backLabel' => __('mirror.title')]);
    }

    /**
     * The own mirror opens in the browser that made it, read or claimed; a
     * claimed one opens for its owner too, in the account.
     */
    private function canSee(MirrorSession $session): bool
    {
        $isOwner = $session->user_id !== null && auth('web')->id() === $session->user_id;

        if ($this->organization !== null) {
            return $isOwner && $session->organization_id === $this->organization->id;
        }

        return $isOwner || in_array($session->token, session('mirror_tokens', []), true);
    }

    private function ensureOwner(): void
    {
        abort_unless($this->canSee($this->session), 403);
    }

    private function currentGroup(MirrorService $mirrors): ?MirrorValueGroup
    {
        return $mirrors->groups($this->session)->where('excluded', false)->values()->get($this->position - 1);
    }

    private function saveCuriosity(): void
    {
        $this->validate(['curiosity' => ['array'], 'curiosity.*' => [Rule::in(MirrorSession::CURIOSITY)]]);
        $this->session->update(['curiosity' => array_values($this->curiosity)]);
    }

    /**
     * A claimed public mirror points to the account: open there, or sign in first.
     */
    private function accountUrl(): ?string
    {
        if ($this->organization !== null || $this->session->isAnonymous()) {
            return null;
        }

        return auth('web')->id() === $this->session->user_id && $this->session->organization !== null
            ? route('client.mirror.result', [$this->session->organization, $this->session])
            : route('client.login');
    }

    private function throttleLogin(): void
    {
        $key = 'mirror-login:'.mb_strtolower((string) $this->session->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['password' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        RateLimiter::hit($key, 60);
    }

    /**
     * Signed in, the mirror goes to the account and opens there, expanded (06 · 6.1).
     */
    private function signIn(User $user, CustomerAuthenticator $authenticator, MirrorService $mirrors): void
    {
        $authenticator->login($user);
        $mirrors->attachToExisting($this->session, $user);
        $this->session->refresh();

        $this->redirect(route('client.mirror.result', [$this->session->organization, $this->session]), navigate: false);
    }
}
