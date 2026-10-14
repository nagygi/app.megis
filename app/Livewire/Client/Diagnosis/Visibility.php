<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Exceptions\DiagnosisException;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\MainDiagnoses\MainDiagnosisService;
use Illuminate\View\View;
use Livewire\Component;

/**
 * V9 · Ki lát mit (23 · 23.4): who sees what, valid until the end; the only
 * button starts the diagnosis. It only informs, nothing can be switched here.
 */
class Visibility extends Component
{
    use InteractsWithMainDiagnosis;

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->isLeader(), 403);
    }

    public function startDiagnosis(MainDiagnosisService $mainDiagnoses): void
    {
        abort_unless($this->isLeader(), 403);

        try {
            $mainDiagnoses->start($this->main(), $this->user());
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        session()->flash('status', __('diagnosis.visibility.started'));
        $this->redirect($this->overviewUrl(), navigate: false);
    }

    public function render(): View
    {
        $steps = $this->steps();
        $missing = $steps->firstMissing(except: DiagnosisStep::Visibility);

        return view('livewire.client.diagnosis.visibility', [
            'main' => $this->main(),
            'missing' => $missing,
            'missingUrl' => $missing === null ? null : $this->stepUrl($missing->step),
            'canStart' => $this->main()->stage->isBeforeRunning() && $missing === null,
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::Visibility))->title(DiagnosisStep::Visibility->label());
    }
}
