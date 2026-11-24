<?php

namespace App\Livewire\Concerns;

use App\Services\Audit\ActivityLogger;
use App\Services\Auth\ImpersonationService;

/**
 * A field that saves when it changes is a change too: it goes into the
 * activity log of its request (docs/uzemeltetes.md · 4), and while an admin
 * works in the customer's name, into the customer's account log (14 · 14.6).
 * Protected, so the browser cannot call it as an action.
 */
trait NotesImpersonatedChanges
{
    protected function noteChange(string $field): void
    {
        app(ActivityLogger::class)->noteChange($this->getName().'::'.$field);
        app(ImpersonationService::class)->noteChange($this->getName().'::'.$field);
    }
}
