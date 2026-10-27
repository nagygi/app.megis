<?php

namespace App\Http\Controllers\Client;

use App\Enums\TokenPurpose;
use App\Http\Controllers\Concerns\ThrottlesAttempts;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\Auth\CustomerAuthenticator;
use App\Services\Auth\LandingResolver;
use App\Services\Auth\LoginCodeService;
use App\Services\Auth\LoginOutcome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * B1 · Belépés (14 · 14.2): "Belépek" sends a code; with "Jelszóval lépek be"
 * the password is checked instead. Unknown, inactive and password-less
 * accounts get the same answer, so nobody learns who has an account.
 */
class CustomerLoginController extends Controller
{
    use ThrottlesAttempts;

    public const CODE_REQUESTS_PER_HOUR = 5;

    public const ATTEMPTS_PER_MINUTE = 5;

    /**
     * The login page for an existing account invited to another organization:
     * B1 says who invited them and where (14 · 14.2). Signed, so the line
     * cannot be made up; it lives as long as an invitation.
     */
    public static function invitedUrl(Organization $organization, User $inviter): string
    {
        return URL::temporarySignedRoute('client.login', TokenPurpose::ClientInvitation->lifetime(), [
            'szervezet' => $organization->id,
            'meghivo' => $inviter->id,
        ]);
    }

    public function create(Request $request): View
    {
        return view('client.auth.login', [
            'email' => $request->session()->get('login.email'),
            'invitation' => $this->invitationContext($request),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(
        Request $request,
        LoginCodeService $codes,
        CustomerAuthenticator $authenticator,
        LandingResolver $landing,
    ): RedirectResponse {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['nullable', 'string'],
            Turnstile::FIELD => [new Turnstile],
        ]);

        $email = $validated['email'];

        if (blank($validated['password'] ?? null)) {
            $this->throttle('login-code', $email, (string) $request->ip(), self::CODE_REQUESTS_PER_HOUR, 3600);

            $codes->send($email);
            $request->session()->put('login.email', $email);

            return redirect()->route('client.login.code')->with('status', __('auth_ui.customer.code_sent'));
        }

        $throttleKey = $this->throttle('customer-login', $email, (string) $request->ip(), self::ATTEMPTS_PER_MINUTE, 60);

        [$outcome, $user] = $authenticator->attemptPassword($email, $validated['password']);

        if ($outcome === LoginOutcome::PasswordPaused) {
            throw ValidationException::withMessages(['password' => __('auth_ui.customer.password_paused')]);
        }

        if ($outcome !== LoginOutcome::Success) {
            throw ValidationException::withMessages(['password' => __('auth_ui.customer.failed')]);
        }

        RateLimiter::clear($throttleKey);
        $authenticator->login($user);

        return redirect()->intended($landing->urlFor($user));
    }

    private function invitationContext(Request $request): ?string
    {
        if (! $request->hasValidSignature()) {
            return null;
        }

        $organization = Organization::query()->find($request->query('szervezet'));
        $inviter = User::query()->find($request->query('meghivo'));

        return $organization !== null && $inviter !== null
            ? __('auth_ui.customer.invitation_context', ['inviter' => $inviter->name, 'organization' => $organization->name])
            : null;
    }
}
