<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Enums\ProcessKind;
use App\Enums\StepMark;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\CalendarConnection;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Models\Process;
use App\Services\Processes\ProcessService;
use Illuminate\View\View;
use Livewire\Component;

/**
 * V6·f · A folyamataid (11 · 11.4): what goes into the diagnosis, what is
 * there but not in the calendar, what stayed out; "Tovább" closes the step.
 */
class ProcessSummary extends Component
{
    use InteractsWithMainDiagnosis;

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->isLeader(), 403);
    }

    public function next(ProcessService $processes): void
    {
        $this->authorizeInput();

        $processes->syncDiagnosisProcesses($this->main());
        $this->markStep(DiagnosisStep::Processes, StepMark::Viewed);

        $this->redirect($this->nextUrl(DiagnosisStep::Processes), navigate: false);
    }

    public function render(): View
    {
        $measured = Process::query()->whereBelongsTo($this->organization)->measured()->with('responsible')->withCount('children')->orderBy('id')->get();
        $connection = CalendarConnection::query()->whereBelongsTo($this->organization)->where('user_id', $this->user()->id)->latest('id')->first();

        return view('livewire.client.diagnosis.process-summary', [
            'inCalendar' => $measured->filter->isInCalendar()->values(),
            'notInCalendar' => $measured->reject->isInCalendar()->values(),
            'measuredCount' => $measured->count(),
            'excluded' => Process::query()->whereBelongsTo($this->organization)->whereNull('parent_id')->where('kind', ProcessKind::Excluded)->orderBy('id')->get(),
            'otherCount' => Process::query()->whereBelongsTo($this->organization)->where('kind', ProcessKind::OneOff)->count()
                + ($connection?->one_off_count ?? 0) + ($connection?->unlinked_count ?? 0),
            'editable' => $this->acceptsInput(),
            'sheetUrl' => fn (Process $process) => route('client.diagnosis.process', [$this->organization, $this->diagnosis, $process]),
        ])->layout('layouts::client', [
            ...$this->stepLayout(DiagnosisStep::Processes, trans_choice('processes.summary.lead', $measured->count(), ['count' => $measured->count()])),
            'title' => __('processes.summary.title'),
            'note' => ($limit = $this->main()->productSettings()['max_measured_processes']) !== null ? __('processes.summary.limit', ['max' => $limit]) : null,
            'back' => route('client.diagnosis.process-list', [$this->organization, $this->diagnosis]),
            'backLabel' => __('processes.list.title'),
        ])->title(__('processes.summary.title'));
    }
}
