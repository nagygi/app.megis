<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Concerns\ThrottlesAttempts;
use App\Http\Controllers\Controller;
use App\Services\Auth\CustomerAuthenticator;
use App\Services\Auth\LandingResolver;
use App\Services\Auth\LoginCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * B2 · Nézd meg a leveled (14 · 14.2): typing the code, for readers on another device.
 */
class LoginCodeController extends Controller
{
    use ThrottlesAttempts;

    public function create(Request $request, LoginCodeService $codes): View|RedirectResponse
    {
        $email = $request->session()->get('login.email');

        if ($email === null) {
            return redirect()->route('client.login');
        }

        return view('client.auth.code', [
            'email' => $email,
            'secondsUntilResend' => $codes->secondsUntilResend($email),
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
        $email = $request->session()->get('login.email');

        if ($email === null) {
            return redirect()->route('client.login');
        }

        $validated = $request->validate(['code' => ['required', 'string']]);

        $throttleKey = $this->throttle(
            'customer-login',
            $email,
            (string) $request->ip(),
            CustomerLoginController::ATTEMPTS_PER_MINUTE,
            60,
            'code',
        );

        $user = $codes->verifyCode($email, $validated['code']);

        if ($user === null) {
            throw ValidationException::withMessages(['code' => __('auth_ui.customer.code_invalid')]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->forget('login.email');
        $authenticator->login($user);

        return redirect()->intended($landing->urlFor($user));
    }

    /**
     * "Újraküldöm" becomes active after 60 seconds (14 · 14.2).
     *
     * @throws ValidationException
     */
    public function resend(Request $request, LoginCodeService $codes): RedirectResponse
    {
        $email = $request->session()->get('login.email');

        if ($email === null) {
            return redirect()->route('client.login');
        }

        $secondsLeft = $codes->secondsUntilResend($email);

        if ($secondsLeft > 0) {
            throw ValidationException::withMessages(['code' => __('auth_ui.customer.resend_wait', ['seconds' => $secondsLeft])]);
        }

        $this->throttle('login-code', $email, (string) $request->ip(), CustomerLoginController::CODE_REQUESTS_PER_HOUR, 3600, 'code');
        $codes->send($email);

        return redirect()->route('client.login.code')->with('status', __('auth_ui.customer.code_sent'));
    }
}
