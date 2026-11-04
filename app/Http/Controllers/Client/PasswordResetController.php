<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Rules\Turnstile;
use App\Services\Auth\ClientPasswordResetService;
use App\Services\Auth\CustomerAuthenticator;
use App\Services\Auth\LandingResolver;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The page of the password link (14 · 14.3). Corporate mail scanners open
 * links in advance, so opening does not use it up; saving the password does,
 * and logs the user in.
 */
class PasswordResetController extends Controller
{
    public function show(string $token, ClientPasswordResetService $resets): View
    {
        $link = $resets->findUsable($token);

        if ($link === null) {
            return view('client.auth.reset-invalid');
        }

        return view('client.auth.reset-password', ['token' => $token, 'user' => $link->user]);
    }

    public function store(
        Request $request,
        string $token,
        ClientPasswordResetService $resets,
        CustomerAuthenticator $authenticator,
        LandingResolver $landing,
    ): RedirectResponse|View {
        $link = $resets->findUsable($token);

        if ($link === null) {
            return view('client.auth.reset-invalid');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', PasswordRules::rule()],
            Turnstile::FIELD => [new Turnstile],
        ]);

        $user = $resets->complete($link, $validated['password']);
        $authenticator->login($user);

        return redirect()->intended($landing->urlFor($user));
    }
}
