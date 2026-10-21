<?php

namespace App\Livewire\Admin\Capabilities;

use App\Enums\PatternSubtype;
use App\Enums\PatternType;
use App\Livewire\Admin\Capabilities\Concerns\ManagesCapabilityState;
use App\Models\Capability;
use App\Models\Pattern;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The patterns of a capability (NM-19): what it looks like in real material,
 * by subtype. A capability without patterns is recognised by interpretation.
 */
class Patterns extends Component
{
    use ManagesCapabilityState;

    public Capability $capability;

    public function mount(Capability $capability): void
    {
        Gate::authorize('view', $capability);

        $this->capability = $capability;
    }

    public function render(): View
    {
        $patterns = $this->capability->patterns()
            ->orderBy('code')
            ->get()
            ->sortBy(fn (Pattern $pattern) => [
                $pattern->type === PatternType::Capability ? 0 : 1,
                $pattern->subtype === null ? count(PatternSubtype::cases()) : array_search($pattern->subtype, PatternSubtype::cases(), true),
            ])
            ->groupBy(fn (Pattern $pattern) => $pattern->subtype?->label() ?? $pattern->type->label());

        return view('livewire.admin.capabilities.patterns', [
            'groups' => $patterns,
        ])->layout('layouts::admin')->title(__('admin.capabilities.tabs.patterns').' · '.$this->capability->name);
    }
}
