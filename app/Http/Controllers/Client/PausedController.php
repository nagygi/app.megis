<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\GuardSession;
use App\Services\Auth\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * No active organization left (14 · 14.2). An admin account that logged in on
 * the customer side (e.g. with a login code) has nothing to see here: it is
 * logged out of the customer side and sent to the admin login instead.
 */
class PausedController extends Controller
{
    public function __invoke(Request $request, GuardSession $guardSession, ImpersonationService $impersonation): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin() && ! $impersonation->isImpersonating()) {
            $guardSession->logout(GuardSession::WEB);

            return redirect()->route('admin.login')->with('status', __('auth_ui.customer.admin_account'));
        }

        return view('client.paused');
    }
}
