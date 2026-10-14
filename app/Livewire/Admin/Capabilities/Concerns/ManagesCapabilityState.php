<?php

namespace App\Livewire\Admin\Capabilities\Concerns;

use App\Services\Capabilities\CapabilityService;
use Illuminate\Support\Facades\Gate;

/**
 * The header actions of a capability page: archive and restore (07 · 7.5).
 * The component holds the capability in $capability.
 */
trait ManagesCapabilityState
{
    public function archive(CapabilityService $capabilities): void
    {
        Gate::authorize('update', $this->capability);

        $capabilities->archive($this->capability);
        session()->flash('status', __('admin.capabilities.archived_flash'));
    }

    public function restore(CapabilityService $capabilities): void
    {
        Gate::authorize('update', $this->capability);

        $capabilities->restore($this->capability);
        session()->flash('status', __('admin.capabilities.restored_flash'));
    }
}
