<?php

namespace App\Livewire\Admin\MainDiagnoses;

use App\Enums\MainDiagnosisStage;
use App\Exceptions\DiagnosisException;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\ReportSnapshot;
use App\Services\Report\PublishService;
use App\Services\Report\TakeawayBuilder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Publikálás (A4, 21): the preview of what the leader and the peers get,
 * the checklist, "Publikálom", corrections after it and the withdrawal.
 */
class Publish extends Component
{
    public Diagnosis $diagnosis;

    public string $device = 'phone';

    public ?string $peerId = null;

    public string $correctionNote = '';

    public string $withdrawReason = '';

    public function mount(Diagnosis $diagnosis, TakeawayBuilder $takeaways): void
    {
        abort_if($diagnosis->main === null, 404);
        Gate::authorize('update', $diagnosis);

        $this->diagnosis = $diagnosis;
        $this->peerId = ($id = $takeaways->recipients($diagnosis->main)->first()?->id) === null ? null : (string) $id;
    }

    /**
     * "Publikálom" (21 · 21.3).
     */
    public function publish(PublishService $publishing): void
    {
        Gate::authorize('update', $this->diagnosis);

        $this->attempt(function () use ($publishing): void {
            $publishing->publish($this->main(), auth('admin')->user());
            session()->flash('status', __('admin.publish.published'));
        });
    }

    /**
     * A correction after publishing, with the "frissítve" marks (21 · 21.4).
     */
    public function publishCorrection(PublishService $publishing): void
    {
        Gate::authorize('update', $this->diagnosis);
        $this->validate(['correctionNote' => ['nullable', 'string', 'max:500']]);

        $this->attempt(function () use ($publishing): void {
            $publishing->publishCorrection($this->main(), auth('admin')->user(), note: filled($this->correctionNote) ? trim($this->correctionNote) : null);
            $this->correctionNote = '';
            session()->flash('status', __('admin.publish.corrected'));
        });
    }

    /**
     * "Visszavonom", with a mandatory reason (21 · 21.5).
     */
    public function withdraw(PublishService $publishing): void
    {
        Gate::authorize('update', $this->diagnosis);
        $this->validate(['withdrawReason' => ['required', 'string', 'min:3', 'max:1000']], attributes: ['withdrawReason' => __('admin.publish.withdraw_reason')]);

        $this->attempt(function () use ($publishing): void {
            $publishing->withdraw($this->main(), auth('admin')->user(), $this->withdrawReason);
            $this->withdrawReason = '';
            session()->flash('status', __('admin.publish.withdrawn'));
        });
    }

    public function render(PublishService $publishing, TakeawayBuilder $takeaways): View
    {
        $main = $this->main()->refresh();

        return view('livewire.admin.main-diagnoses.publish', [
            'main' => $main,
            'checklist' => $publishing->checklist($main),
            'canPublish' => $publishing->canPublish($main),
            'recipients' => $takeaways->recipients($main),
            'snapshots' => ReportSnapshot::query()->where('main_diagnosis_id', $main->id)->latest('sequence')->get(['id', 'sequence', 'reason', 'note', 'created_at', 'created_by']),
            'isPublished' => $main->stage === MainDiagnosisStage::Published,
            'isReading' => $main->stage === MainDiagnosisStage::Reading,
        ])->layout('layouts::admin', ['organization' => $this->diagnosis->organization])->title($this->diagnosis->name);
    }

    private function main(): MainDiagnosis
    {
        return $this->diagnosis->main;
    }

    private function attempt(callable $change): void
    {
        try {
            $change();
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }
}
