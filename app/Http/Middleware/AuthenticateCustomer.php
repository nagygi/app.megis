<?php

namespace App\Http\Middleware;

use App\Enums\AuditEventType;
use App\Models\Impersonation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\AdminSessionState;
use App\Services\Auth\GuardSession;
use App\Services\Auth\ImpersonationService;
use App\Support\CurrentOrganization;
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
        private AuditLogger $audit,
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

        if (! $user->isActive() || ! $user->isActivated() || ! $this->guardSession->epochMatches(GuardSession::WEB, $user)) {
            $this->guardSession->logout(GuardSession::WEB);

            return redirect()->guest(route('client.login'));
        }

        Auth::shouldUse(GuardSession::WEB);

        $response = $next($request);

        // Refused requests changed nothing; a request that ended the work is logged by the end itself.
        $changed = $response->isSuccessful() || $response->isRedirection();

        if ($impersonation !== null && $changed && $this->impersonation->isImpersonating()) {
            $this->logChange($request, $impersonation);
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
     * Records which admin changed what in the customer's name. Reads are not changes;
     * Livewire requests count only when they call an action, not when they sync a field.
     */
    private function logChange(Request $request, Impersonation $impersonation): void
    {
        if ($request->isMethodSafe()) {
            return;
        }

        $actions = $request->hasHeader('X-Livewire')
            ? $this->livewireActions($request)
            : [$request->route()?->getName() ?? $request->path()];

        if ($actions === []) {
            return;
        }

        $this->audit->record(
            AuditEventType::ImpersonationAction,
            $impersonation->user,
            $impersonation->admin,
            ['actions' => $actions],
            $impersonation,
            app(CurrentOrganization::class)->get(),
        );
    }

    /**
     * @return list<string>
     */
    private function livewireActions(Request $request): array
    {
        return collect($request->input('components', []))
            ->flatMap(function (array $component): array {
                $name = json_decode($component['snapshot'] ?? '{}', true)['memo']['name'] ?? 'livewire';

                return collect($component['calls'] ?? [])
                    ->map(fn (array $call): string => $name.'::'.($call['method'] ?? '?'))
                    ->all();
            })
            ->values()
            ->all();
    }
}
