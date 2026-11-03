<?php

namespace App\Http\Middleware;

use App\Models\Impersonation;
use App\Models\User;
use App\Services\Auth\AdminSessionState;
use App\Services\Auth\GuardSession;
use App\Services\Auth\ImpersonationService;
use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Customer side access (14 · 14.2). While an admin works in the customer's name,
 * the work ends with the admin session, and every change is logged (14 · 14.6).
 */
class AuthenticateCustomer implements AuthenticatesRequests
{
    public function __construct(
        private GuardSession $guardSession,
        private ImpersonationService $impersonation,
        private AdminSessionState $adminSession,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->impersonation->dropEnded()) {
            return redirect()->route('admin.login');
        }

        $impersonation = $this->impersonation->current();

        if ($impersonation !== null && ! $this->adminSessionAlive($impersonation)) {
            $this->impersonation->end(Impersonation::END_ADMIN_SESSION_EXPIRED);

            return redirect()->route('admin.login');
        }

        if ($impersonation !== null) {
            $this->impersonation->markSeen($impersonation);
        }

        /** @var User|null $user */
        $user = Auth::guard(GuardSession::WEB)->user();

        if ($user === null) {
            return redirect()->guest(route('client.login'));
        }

        // A not yet registered account is only reached in its name (14 · 14.6).
        $isRegisteredOrImpersonated = $user->isActivated() || $impersonation !== null;

        if (! $user->isActive() || ! $isRegisteredOrImpersonated || ! $this->guardSession->epochMatches(GuardSession::WEB, $user)) {
            $this->guardSession->logout(GuardSession::WEB);

            return redirect()->guest(route('client.login'));
        }

        Auth::shouldUse(GuardSession::WEB);

        $response = $next($request);

        // Refused requests changed nothing; a request that ended the work is logged by the end itself.
        $changed = $response->isSuccessful() || $response->isRedirection();

        if ($impersonation !== null && $changed && $this->impersonation->isImpersonating()) {
            $this->logChange($request);
        }

        return $response;
    }

    private function adminSessionAlive(Impersonation $impersonation): bool
    {
        /** @var User|null $admin */
        $admin = Auth::guard(GuardSession::ADMIN)->user();

        return $admin !== null
            && $admin->is($impersonation->admin)
            && $this->adminSession->isAlive($admin);
    }

    /**
     * Records which admin changed what in the customer's name; reads are not
     * changes. A Livewire update is logged after its components ran
     * (AppServiceProvider): here, as persistent middleware, it runs before
     * them and with the page's GET method.
     */
    private function logChange(Request $request): void
    {
        if ($request->isMethodSafe() || $request->hasHeader('X-Livewire')) {
            return;
        }

        $this->impersonation->logActions([$request->route()?->getName() ?? $request->path()]);
    }
}
