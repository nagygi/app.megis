<?php

namespace App\Services\Auth;

use App\Enums\AuditEventType;
use App\Models\Impersonation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Log;

/**
 * An admin working in a customer's name on the customer side (14 · 14.6).
 *
 * The admin stays logged in on the admin guard; the customer guard is logged
 * in as the customer for the length of the session.
 */
class ImpersonationService
{
    public const SESSION_KEY = 'impersonation_id';

    private ?Impersonation $current = null;

    public function __construct(
        private Session $session,
        private GuardSession $guardSession,
        private AuditLogger $audit,
    ) {}

    public function start(User $admin, User $user): Impersonation
    {
        $this->end(Impersonation::END_EXIT);

        $impersonation = Impersonation::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'started_at' => now(),
        ]);

        // last_login_at is deliberately left alone: this is not the customer logging in.
        $this->guardSession->login(GuardSession::WEB, $user);
        $this->session->put(self::SESSION_KEY, $impersonation->id);
        $this->current = $impersonation;

        $this->audit->record(AuditEventType::ImpersonationStarted, $user, $admin, impersonation: $impersonation);
        Log::channel('auth')->info('Impersonation started.', ['admin_id' => $admin->id, 'user_id' => $user->id]);

        return $impersonation;
    }

    public function current(): ?Impersonation
    {
        $impersonationId = $this->session->get(self::SESSION_KEY);

        if ($impersonationId === null) {
            return null;
        }

        if ($this->current?->id !== $impersonationId) {
            $this->current = Impersonation::query()->with(['admin', 'user'])->find($impersonationId);
        }

        return $this->current?->isActive() ? $this->current : null;
    }

    public function isImpersonating(): bool
    {
        return $this->current() !== null;
    }

    public function end(string $reason): void
    {
        $impersonation = $this->current();

        if ($impersonation === null) {
            $this->session->forget(self::SESSION_KEY);

            return;
        }

        $impersonation->forceFill(['ended_at' => now(), 'end_reason' => $reason])->save();
        $this->session->forget(self::SESSION_KEY);
        $this->current = null;

        $this->audit->record(
            AuditEventType::ImpersonationEnded,
            $impersonation->user,
            $impersonation->admin,
            ['reason' => $reason],
            $impersonation,
        );

        $this->guardSession->logout(GuardSession::WEB);
    }
}
