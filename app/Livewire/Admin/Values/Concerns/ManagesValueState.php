<?php

namespace App\Livewire\Admin\Values\Concerns;

use App\Services\Values\ValueService;
use Illuminate\Support\Facades\Gate;

/**
 * The header actions of a value page: archive, restore (03 · 3.5) and
 * "Átnézve" (03 · 3.7). The component holds the value in $value.
 */
trait ManagesValueState
{
    public function archive(ValueService $values): void
    {
        Gate::authorize('update', $this->value);

        $values->archive($this->value);
        session()->flash('status', __('admin.values.archived_flash'));
    }

    public function restore(ValueService $values): void
    {
        Gate::authorize('update', $this->value);

        $values->restore($this->value);
        session()->flash('status', __('admin.values.restored_flash'));
    }

    public function markReviewed(ValueService $values): void
    {
        Gate::authorize('update', $this->value);

        $values->markReviewed($this->value);
        session()->flash('status', __('admin.values.reviewed_flash'));
    }
}
