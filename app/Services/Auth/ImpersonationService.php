<?php

namespace App\Services\Auth;

use App\Enums\AuditEventType;
use App\Models\Impersonation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\CurrentOrganization;
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

    /**
     * Livewire actions that change nothing, called on a timer (14 · 14.6).
     */
    private const NOT_CHANGES = ['pollDictations'];

    private ?Impersonation $current = null;

    /**
     * The fields saved in the customer's name during this request.
     *
     * @var list<string>
     */
    private array $changes = [];

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
            'last_seen_at' => now(),
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

    /**
     * A field saved in the customer's name: logged with its request (14 · 14.6).
     */
    public function noteChange(string $change): void
    {
        if ($this->isImpersonating()) {
            $this->changes[] = $change;
        }
    }

    /**
     * Records which admin changed what in the customer's name: the actions
     * of the request and the fields it saved (14 · 14.6, 33 · 33.6).
     *
     * @param  list<string>  $actions
     */
    public function logActions(array $actions): void
    {
        $actions = array_values(array_unique([...$actions, ...$this->changes]));
        $this->changes = [];
        $impersonation = $this->current();

        if ($impersonation === null || $actions === []) {
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
     * A Livewire update, after its components ran without an error: the
     * actions it called and the fields its hooks saved. Livewire's own calls
     * ($refresh, $set, _startUpload…) and the polls are no changes, and an
     * admin page is not the customer's (14 · 14.6).
     *
     * @param  array<int, mixed>  $components
     */
    public function logLivewireRequest(array $components): void
    {
        $actions = collect($components)
            ->filter(fn ($component) => is_array($component))
            ->flatMap(function (array $component): array {
                $name = json_decode($component['snapshot'] ?? '{}', true)['memo']['name'] ?? 'livewire';

                if (str_starts_with($name, 'admin.')) {
                    return [];
                }

                return collect($component['calls'] ?? [])
                    ->pluck('method')
                    ->filter(fn ($method) => is_string($method)
                        && ! str_starts_with($method, '$')
                        && ! str_starts_with($method, '_')
                        && ! in_array($method, self::NOT_CHANGES, true))
                    ->map(fn (string $method): string => $name.'::'.$method)
                    ->all();
            })
            ->values()
            ->all();

        $this->logActions($actions);
    }

    /**
     * A request in the customer's name; written at most once a minute.
     */
    public function markSeen(Impersonation $impersonation): void
    {
        if ($impersonation->last_seen_at === null || $impersonation->last_seen_at->lt(now()->subMinute())) {
            $impersonation->forceFill(['last_seen_at' => now()])->save();
        }
    }

    /**
     * The session still names a work that was closed elsewhere, e.g. as
     * abandoned (33 · 33.6). The customer guard goes with it, so the admin
     * never carries on as the customer without the log.
     */
    public function dropEnded(): bool
    {
        if ($this->session->get(self::SESSION_KEY) === null || $this->current() !== null) {
            return false;
        }

        $this->session->forget(self::SESSION_KEY);
        $this->guardSession->logout(GuardSession::WEB);

        return true;
    }

    /**
     * Closes the works the admin left without "Kilépés": no request in the
     * customer's name for the admin idle time (01, 33 · 33.6). The work ended
     * with its last request, as nothing happened after it.
     */
    public function closeAbandoned(): int
    {
        $cutoff = now()->subMinutes((int) config('megis.admin_idle_timeout_minutes'));

        return Impersonation::query()
            ->whereNull('ended_at')
            ->where(fn ($query) => $query
                ->where('last_seen_at', '<', $cutoff)
                ->orWhere(fn ($unseen) => $unseen->whereNull('last_seen_at')->where('started_at', '<', $cutoff)))
            ->with(['admin', 'user'])
            ->get()
            ->each(function (Impersonation $impersonation): void {
                $endedAt = $impersonation->last_seen_at ?? $impersonation->started_at;
                $impersonation->forceFill(['ended_at' => $endedAt, 'end_reason' => Impersonation::END_ABANDONED])->save();

                $this->audit->record(
                    AuditEventType::ImpersonationEnded,
                    $impersonation->user,
                    $impersonation->admin,
                    ['reason' => Impersonation::END_ABANDONED],
                    $impersonation,
                    at: $endedAt,
                );
            })
            ->count();
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
