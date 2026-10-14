<?php

namespace App\Services\Audit;

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\Impersonation;
use App\Models\User;

/**
 * Writes the customer account log shown on the admin datasheet (33 · 33.6).
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        AuditEventType $type,
        ?User $subject,
        ?User $actor = null,
        array $payload = [],
        ?Impersonation $impersonation = null,
    ): AuditEvent {
        return AuditEvent::create([
            'type' => $type,
            'subject_user_id' => $subject?->id,
            'actor_id' => $actor?->id,
            'impersonation_id' => $impersonation?->id,
            'payload' => $payload === [] ? null : $payload,
        ]);
    }
}
