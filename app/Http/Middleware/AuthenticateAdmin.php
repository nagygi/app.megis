<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\AdminSessionState;
use App\Services\Auth\GuardSession;
use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin area access (01 · 1.2): an active admin on the admin guard, whose
 * session is not older than the last password change and not idle for 6 hours.
 */
class AuthenticateAdmin implements AuthenticatesRequests
{
    public function __construct(
        private GuardSession $guardSession,
        private AdminSessionState $adminSession,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $admin */
        $admin = Auth::guard(GuardSession::ADMIN)->user();

        if ($admin === null) {
            // A logged-in customer gets "Nincs jogosultságod", not the admin login.
            abort_if(Auth::guard(GuardSession::WEB)->check(), 403);

            return redirect()->guest(route('admin.login'));
        }

        if (! $this->adminSession->isAlive($admin)) {
            $this->guardSession->logout(GuardSession::ADMIN);

            return redirect()->guest(route('admin.login'))->with('status', __('auth_ui.admin.session_expired'));
        }

        abort_unless($admin->isAdmin(), 403);

        Auth::shouldUse(GuardSession::ADMIN);
        $this->guardSession->touchAdminActivity();

        return $next($request);
    }
}
