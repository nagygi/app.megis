<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Concerns\ThrottlesAttempts;
use App\Http\Controllers\Controller;
use App\Rules\Turnstile;
use App\Services\Auth\CustomerAuthenticator;
use App\Services\Auth\LandingResolver;
use App\Services\Auth\LoginCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The login link from the e-mail (14 · 14.2). Corporate mail scanners open
 * links in advance, so opening only shows a button; pressing it logs in.
 */
class LoginLinkController extends Controller
{
    use ThrottlesAttempts;

    public function show(string $token, LoginCodeService $codes): View
    {
        $linkToken = $codes->findLinkToken($token);

        if ($linkToken === null || ! $linkToken->isPending()) {
            return view('client.auth.link-expired', ['token' => $token, 'canResend' => $linkToken !== null]);
        }

        return view('client.auth.link', ['token' => $token]);
    }

    public function store(string $token, LoginCodeService $codes, CustomerAuthenticator $authenticator, LandingResolver $landing): RedirectResponse
    {
        $user = $codes->useLink($token);

        if ($user === null) {
            return redirect()->route('client.login.link', $token);
        }

        $authenticator->login($user);

        return redirect()->intended($landing->urlFor($user));
    }

    /**
     * "Újat kérek": unlike an invitation, a login code may be requested again
     * by the user, with Turnstile and the hourly limit of B1 (14 · 14.2).
     *
     * @throws ValidationException
     */
    public function resend(Request $request, string $token, LoginCodeService $codes): RedirectResponse
    {
        $linkToken = $codes->findLinkToken($token);

        if ($linkToken === null) {
            return redirect()->route('client.login');
        }

        $request->validate([Turnstile::FIELD => [new Turnstile]]);

        $email = $linkToken->user->email;

        if ($codes->secondsUntilResend($email) === 0) {
            $this->throttle('login-code', $email, (string) $request->ip(), CustomerLoginController::CODE_REQUESTS_PER_HOUR, 3600, Turnstile::FIELD);
            $codes->send($email);
        }

        $request->session()->put('login.email', $email);

        return redirect()->route('client.login.code')->with('status', __('auth_ui.customer.code_sent'));
    }
}
