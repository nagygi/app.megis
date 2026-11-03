<?php

namespace App\Livewire\Concerns;

use App\Services\Auth\ImpersonationService;

/**
 * A field that saves when it changes is a change too while an admin works
 * in the customer's name: it goes into the log of its request (14 · 14.6).
 * Protected, so the browser cannot call it as an action.
 */
trait NotesImpersonatedChanges
{
    protected function noteChange(string $field): void
    {
        app(ImpersonationService::class)->noteChange($this->getName().'::'.$field);
    }
}
