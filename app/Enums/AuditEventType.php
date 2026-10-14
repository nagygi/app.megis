<?php

namespace App\Enums;

/**
 * Entries of the user log shown on the customer datasheet (33 · 33.6).
 */
enum AuditEventType: string
{
    case ImpersonationStarted = 'impersonation_started';
    case ImpersonationEnded = 'impersonation_ended';
    case ImpersonationAction = 'impersonation_action';
    case EmailChanged = 'email_changed';

    public function label(): string
    {
        return __('enums.audit_event_type.'.$this->value);
    }
}
