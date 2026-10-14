<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Enums\StepMark;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\Organization;
use Illuminate\View\View;
use Livewire\Component;

/**
 * V6 · Folyamatok (11 · 11.1): two ways to the detected processes, from the
 * calendar (09) or from a conversation (10), or "Kihagyom".
 */
class Processes extends Component
{
    use InteractsWithMainDiagnosis;

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->isLeader(), 403);
    }

    /**
     * "Kihagyom": the step counts as done and can be made up later (11 · 11.1).
     */
    public function skip(): void
    {
        $this->authorizeInput();

        $this->markStep(DiagnosisStep::Processes, StepMark::Skipped);
        $this->redirect($this->nextUrl(DiagnosisStep::Processes), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.client.diagnosis.processes', [
            'main' => $this->main(),
            'hasProcesses' => $this->organization->processes()->exists(),
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::Processes))->title(DiagnosisStep::Processes->label());
    }
}
