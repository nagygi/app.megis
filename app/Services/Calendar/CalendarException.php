<?php

namespace App\Services\Calendar;

use RuntimeException;

/**
 * A calendar could not be read: "Most nem sikerült…" (09 · 9.6). An expired
 * or withdrawn access breaks a running connection.
 */
class CalendarException extends RuntimeException
{
    public function __construct(string $message = '', public bool $accessLost = false, public bool $needsAdminConsent = false)
    {
        parent::__construct($message);
    }
}
