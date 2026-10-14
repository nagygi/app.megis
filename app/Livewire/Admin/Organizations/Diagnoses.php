<?php

namespace App\Livewire\Admin\Organizations;

use App\Models\Diagnosis;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The organization's "Diagnózisok" tab (17 · 17.8): its main and manual
 * diagnoses. Subdiagnoses are listed under their main diagnosis.
 */
class Diagnoses extends Component
{
    public Organization $organization;

    public function mount(Organization $organization): void
    {
        Gate::authorize('view', $organization);

        $this->organization = $organization;
    }

    public function render(): View
    {
        return view('livewire.admin.organizations.diagnoses', [
            'diagnoses' => Diagnosis::query()
                ->whereBelongsTo($this->organization)
                ->whereNull('parent_id')
                ->with(['type', 'main', 'team', 'user'])
                ->orderByDesc('diagnosed_on')
                ->orderByDesc('id')
                ->get(),
        ])->layout('layouts::admin', ['organization' => $this->organization])->title($this->organization->name);
    }
}
