<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\Turnstile;
use App\Services\Admins\AdminUserService;
use App\Services\Auth\GuardSession;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Password page of the admin invitation and reset links (01 · 1.3). Opening
 * the link does not use it up; saving the password does.
 */
class AdminPasswordSetupController extends Controller
{
    public function create(string $token, AdminUserService $admins): View
    {
        $link = $admins->findUsableLink($token);

        if ($link === null) {
            return view('admin.auth.setup-invalid');
        }

        return view('admin.auth.setup', ['token' => $token, 'user' => $link->user]);
    }

    public function store(Request $request, string $token, AdminUserService $admins, GuardSession $guardSession): RedirectResponse|View
    {
        $link = $admins->findUsableLink($token);

        if ($link === null) {
            return view('admin.auth.setup-invalid');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', PasswordRules::admin()],
            Turnstile::FIELD => [new Turnstile],
        ]);

        $user = $admins->completePasswordSetup($link, $validated['password']);
        $guardSession->login(GuardSession::ADMIN, $user);

        return redirect()->route('admin.dashboard');
    }
}
