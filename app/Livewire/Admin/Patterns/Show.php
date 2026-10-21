<?php

namespace App\Livewire\Admin\Patterns;

use App\Models\Pattern;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * A pattern of the Mintatár (NM-19), read only: the mégis Sheet is the
 * master, a change comes with the next load as a new version.
 */
class Show extends Component
{
    public Pattern $pattern;

    public function mount(Pattern $pattern): void
    {
        Gate::authorize('view', $pattern);

        $this->pattern = $pattern->load(['capabilities', 'preconditions', 'latestVersion']);
    }

    public function render(): View
    {
        return view('livewire.admin.patterns.show')
            ->layout('layouts::admin')
            ->title($this->pattern->code.' · '.$this->pattern->name);
    }
}
