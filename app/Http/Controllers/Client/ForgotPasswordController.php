<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Concerns\ThrottlesAttempts;
use App\Http\Controllers\Controller;
use App\Rules\Turnstile;
use App\Services\Auth\ClientPasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Elfelejtettem a jelszavam" from B1 (14 · 14.3). Unknown, inactive, not
 * registered and admin accounts get the same answer and no mail, so nobody
 * learns who has an account.
 */
class ForgotPasswordController extends Controller
{
    use ThrottlesAttempts;

    public const REQUESTS_PER_HOUR = 5;

    public function create(Request $request): View
    {
        return view('client.auth.forgot-password', ['email' => $request->session()->get('login.email')]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, ClientPasswordResetService $resets): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            Turnstile::FIELD => [new Turnstile],
        ]);

        $email = $validated['email'];

        $this->throttle('password-reset', $email, (string) $request->ip(), self::REQUESTS_PER_HOUR, 3600);

        $resets->request($email);
        $request->session()->put('login.email', $email);

        return redirect()->route('client.login')->with('status', __('auth_ui.customer.reset_link_sent'));
    }
}
