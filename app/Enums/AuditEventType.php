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
    // A peer's consent on T1 and its withdrawal (25 · 25.2, 31 · 31.6).
    case ConsentGiven = 'consent_given';
    case ConsentRevoked = 'consent_revoked';
    // "Adataim törlése" asked for, and done (31 · 31.7).
    case AccountDeletionRequested = 'account_deletion_requested';
    case AccountDeleted = 'account_deleted';
    // An admin inactivated or activated the account (33 · 33.2).
    case AccountDeactivated = 'account_deactivated';
    case AccountActivated = 'account_activated';
    // Felvételeim: a recording's audio deleted (31 · 31.5).
    case RecordingDeleted = 'recording_deleted';
    // An admin loaded the leader's calendar file or link at the leader's request (09 · 9.2).
    case CalendarConnectedByAdmin = 'calendar_connected_by_admin';

    public function label(): string
    {
        return __('enums.audit_event_type.'.$this->value);
    }
}
