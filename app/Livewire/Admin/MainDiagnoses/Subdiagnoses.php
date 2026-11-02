<?php

namespace App\Livewire\Admin\MainDiagnoses;

use App\Models\Diagnosis;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * "Részdiagnózisok" of a main diagnosis (17 · 17.4): name, level, type, user
 * and status. They are approved and published with the main diagnosis.
 */
class Subdiagnoses extends Component
{
    public Diagnosis $diagnosis;

    public function mount(Diagnosis $diagnosis): void
    {
        Gate::authorize('view', $diagnosis);
        abort_if($diagnosis->main === null, 404);

        $this->diagnosis = $diagnosis;
    }

    public function render(): View
    {
        return view('livewire.admin.main-diagnoses.subdiagnoses', [
            'subdiagnoses' => $this->diagnosis->subdiagnoses()
                ->with(['type', 'user', 'team'])
                ->orderBy('diagnosed_on')
                ->orderBy('id')
                ->get(),
        ])->layout('layouts::admin')->title($this->diagnosis->name);
    }
}
