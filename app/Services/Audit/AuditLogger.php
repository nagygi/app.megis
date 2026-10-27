<?php

namespace App\Services\Audit;

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\Impersonation;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Writes the customer account log shown on the admin datasheet (33 · 33.6).
 * An entry that belongs to an organization lives as long as it (33 · 33.7).
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
        ?Organization $organization = null,
        ?CarbonInterface $at = null,
    ): AuditEvent {
        $event = new AuditEvent([
            'type' => $type,
            'subject_user_id' => $subject?->id,
            'actor_id' => $actor?->id,
            'impersonation_id' => $impersonation?->id,
            'organization_id' => $organization?->id,
            'payload' => $payload === [] ? null : $payload,
        ]);

        // When it happened earlier than now, e.g. the end of an abandoned work.
        if ($at !== null) {
            $event->created_at = $at;
        }

        $event->save();

        return $event;
    }
}
