<?php

namespace App\Http\Controllers;

use App\Enums\TokenPurpose;
use App\Models\UserToken;
use App\Services\Auth\CustomerAuthenticator;
use App\Services\Auth\LandingResolver;
use App\Services\Auth\TokenService;
use App\Services\Mirror\MirrorService;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The confirmation link of the account started on ET5 (06 · 6.1): a short form
 * with the name and an optional password. Opening the link does not use it;
 * saving the form does (14 · 14.1).
 */
class MirrorConfirmationController extends Controller
{
    public function __construct(private TokenService $tokens) {}

    public function show(string $token): View
    {
        $userToken = $this->pendingToken($token);

        return $userToken === null
            ? view('mirror.confirmation-expired')
            : view('mirror.confirmation', ['token' => $token, 'email' => $userToken->user->email]);
    }

    public function store(Request $request, string $token, MirrorService $mirrors, CustomerAuthenticator $authenticator, LandingResolver $landing): View|RedirectResponse
    {
        $userToken = $this->pendingToken($token);

        if ($userToken === null) {
            return view('mirror.confirmation-expired');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'confirmed', PasswordRules::rule()],
        ], attributes: [
            'first_name' => __('auth_ui.registration.first_name'),
            'last_name' => __('auth_ui.registration.last_name'),
            'password' => __('auth_ui.password'),
        ]);

        $user = $mirrors->confirm($userToken, $validated['first_name'], $validated['last_name'], $validated['password'] ?? null);
        $authenticator->login($user);
        $request->session()->regenerate();

        return redirect()->to($landing->urlFor($user));
    }

    private function pendingToken(string $token): ?UserToken
    {
        $userToken = $this->tokens->find($token, TokenPurpose::MirrorConfirmation);

        return $userToken !== null && $userToken->isPending() ? $userToken : null;
    }
}
