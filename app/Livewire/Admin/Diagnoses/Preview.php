<?php

namespace App\Livewire\Admin\Diagnoses;

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisStatus;
use App\Exceptions\DiagnosisException;
use App\Models\Diagnosis;
use App\Services\Diagnoses\DiagnosisService;
use App\Services\Report\ReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Előnézet (04 · 4.6): the report a standalone diagnosis sends out, in a
 * phone or desktop frame, clickable, before publishing; who will read it,
 * and the publishing step next to it (like 21 · 21.1).
 */
class Preview extends Component
{
    public Diagnosis $diagnosis;

    public string $device = 'phone';

    public function mount(Diagnosis $diagnosis): void
    {
        Gate::authorize('view', $diagnosis);
        // A main diagnosis has its Publikálás tab, a subdiagnosis no report.
        abort_if($diagnosis->parent_id !== null || $diagnosis->main()->exists(), 404);

        $this->diagnosis = $diagnosis;
    }

    public function publish(DiagnosisService $diagnoses): void
    {
        Gate::authorize('update', $this->diagnosis);

        try {
            $diagnoses->transition($this->diagnosis, DiagnosisStatus::Published);
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        session()->flash('status', __('admin.diagnoses.status_changed', ['status' => DiagnosisStatus::Published->label()]));
        $this->redirectRoute('admin.diagnoses.show', $this->diagnosis, navigate: false);
    }

    public function render(ReportService $reports): View
    {
        $this->diagnosis->refresh()->load(['organization', 'team', 'user', 'process']);
        $viewers = $reports->standaloneViewers($this->diagnosis);

        return view('livewire.admin.diagnoses.preview', [
            'viewerLine' => match (true) {
                $viewers->isEmpty() => __('admin.diagnoses.preview.nobody'),
                $this->diagnosis->level === DiagnosisLevel::User => __('admin.diagnoses.preview.viewers.user', ['name' => $viewers->first()->name]),
                $this->diagnosis->level === DiagnosisLevel::Team => __('admin.diagnoses.preview.viewers.team', ['team' => $this->diagnosis->team?->name, 'count' => $viewers->count()]),
                default => __('admin.diagnoses.preview.viewers.organization', ['organization' => $this->diagnosis->organization->name, 'count' => $viewers->count()]),
            },
            'canPublish' => in_array(DiagnosisStatus::Published, $this->diagnosis->status->nextStatuses(), true),
        ])->layout('layouts::admin', ['organization' => $this->diagnosis->organization])->title(__('admin.diagnoses.preview.title').' · '.$this->diagnosis->name);
    }
}
