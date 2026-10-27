<?php

namespace App\Models;

use App\Enums\AuditEventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entry of a customer's account log (33 · 33.6). Written once, never updated.
 * An entry of an organization goes when the organization does (33 · 33.7).
 */
#[Fillable(['type', 'actor_id', 'subject_user_id', 'impersonation_id', 'organization_id', 'payload'])]
class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AuditEventType::class,
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    /**
     * @return BelongsTo<Impersonation, $this>
     */
    public function impersonation(): BelongsTo
    {
        return $this->belongsTo(Impersonation::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * What a request in the customer's name changed, by page, e.g.
     * "Saját értékek (save)" (33 · 33.6). An unknown target stays as logged.
     *
     * @return list<string>
     */
    public function actionLabels(): array
    {
        $pages = __('admin.clients.log_pages');

        return collect($this->payload['actions'] ?? [])
            ->map(function (string $action) use ($pages): string {
                [$target, $method] = array_pad(explode('::', $action, 2), 2, null);
                $page = $pages[$target] ?? $target;

                return $method === null ? $page : __('admin.clients.log_action', ['page' => $page, 'method' => $method]);
            })
            ->values()
            ->all();
    }

    /**
     * The detail line under the entry on the datasheet, if it has one (33 · 33.6).
     */
    public function detail(): ?string
    {
        $payload = $this->payload ?? [];

        return match ($this->type) {
            AuditEventType::ImpersonationAction => __('admin.clients.log_actions', ['actions' => implode(', ', $this->actionLabels())]),
            AuditEventType::ImpersonationEnded => isset($payload['reason']) ? __('admin.clients.log_end_reasons.'.$payload['reason']) : null,
            AuditEventType::ConsentGiven, AuditEventType::ConsentRevoked => collect([
                isset($payload['kind']) ? __('enums.consent_kind.'.$payload['kind']) : null,
                $payload['diagnosis'] ?? null,
            ])->filter()->implode(' · ') ?: null,
            AuditEventType::RecordingDeleted => isset($payload['date']) ? __('admin.clients.log_recording', ['date' => $payload['date']]) : null,
            AuditEventType::CalendarConnectedByAdmin => isset($payload['provider']) ? __('calendar.providers.'.$payload['provider']) : null,
            default => null,
        };
    }
}
