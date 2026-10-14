<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ThrottlesAttempts;
use App\Http\Controllers\Controller;
use App\Models\Impersonation;
use App\Rules\Turnstile;
use App\Services\Auth\AdminAuthenticator;
use App\Services\Auth\GuardSession;
use App\Services\Auth\ImpersonationService;
use App\Services\Auth\LoginOutcome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * /admin/belepes (01 · 1.2): e-mail, password and Turnstile. No "remember me",
 * no self-service password reset.
 */
class AdminLoginController extends Controller
{
    use ThrottlesAttempts;

    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, AdminAuthenticator $authenticator, GuardSession $guardSession): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            Turnstile::FIELD => [new Turnstile],
        ]);

        $throttleKey = $this->throttle('admin-login', $validated['email'], (string) $request->ip(), 5, 60);

        [$outcome, $user] = $authenticator->attempt($validated['email'], $validated['password']);

        if ($outcome === LoginOutcome::Locked) {
            throw ValidationException::withMessages(['email' => __('auth_ui.admin.locked')]);
        }

        if ($outcome !== LoginOutcome::Success) {
            throw ValidationException::withMessages(['email' => __('auth_ui.admin.failed')]);
        }

        RateLimiter::clear($throttleKey);
        $guardSession->login(GuardSession::ADMIN, $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Logging out of the admin also ends any work in a customer's name (14 · 14.6).
     */
    public function destroy(ImpersonationService $impersonation, GuardSession $guardSession): RedirectResponse
    {
        $impersonation->end(Impersonation::END_ADMIN_LOGOUT);
        $guardSession->logout(GuardSession::ADMIN);

        return redirect()->route('admin.login');
    }
}
