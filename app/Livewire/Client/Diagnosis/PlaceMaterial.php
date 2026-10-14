<?php

namespace App\Livewire\Client\Diagnosis;

use App\Livewire\Client\Diagnosis\Concerns\HandlesOccasionMaterials;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Process;
use App\Services\Processes\OccasionService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * V11·b · Hova tartozik (12 · 12.2): the routine, the date, and for a
 * recording or a transcript the consent of the participants.
 */
class PlaceMaterial extends Component
{
    use HandlesOccasionMaterials, InteractsWithMainDiagnosis;

    public string $materialUuid = '';

    /** A process id, or "none" for "Egyikhez sem". */
    public string $choice = '';

    public string $date = '';

    public bool $consent = false;

    public function mount(Organization $organization, Diagnosis $diagnosis, Material $material, OccasionService $occasions): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        $this->authorizeContribution();

        $material = $this->ownMaterial($material->uuid);
        $this->materialUuid = $material->uuid;

        if ($material->placed_at !== null) {
            $this->redirect($this->contributeUrl(), navigate: false);

            return;
        }

        $this->date = today()->toDateString();
        $latest = $occasions->latestCalendarOccasion($occasions->eligibleProcesses($this->main(), $this->user()));

        // The latest occasion from the calendar comes selected, with its date (12 · 12.2).
        if ($latest !== null) {
            $this->choice = (string) $latest->process_id;
            $this->date = $latest->occurred_at->toDateString();
        }
    }

    public function place(OccasionService $occasions): void
    {
        $this->authorizeContribution();
        $material = $this->ownMaterial($this->materialUuid);
        $eligible = $occasions->eligibleProcesses($this->main(), $this->user());

        $this->validate([
            'choice' => ['required', Rule::in([...$eligible->pluck('id')->map(fn ($id) => (string) $id)->all(), 'none'])],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'consent' => [Rule::excludeIf($material->is_situation), 'accepted'],
        ], [
            'consent.accepted' => __('processes.occasions.consent_required'),
        ], [
            'choice' => __('processes.occasions.which'),
            'date' => __('processes.occasions.when'),
        ]);

        $process = $this->choice === 'none' ? null : Process::query()->findOrFail((int) $this->choice);
        $occasions->place($material, $process, $this->date, $this->user(), ! $material->is_situation && $this->consent);

        if ($process === null) {
            session()->flash('status', __('processes.occasions.sent_to_admin'));
            $this->redirect($this->contributeUrl(), navigate: false);

            return;
        }

        $this->redirect(route('client.diagnosis.occasion-experience', [$this->organization, $this->diagnosis, $material]), navigate: false);
    }

    public function render(OccasionService $occasions): View
    {
        $eligible = $occasions->eligibleProcesses($this->main(), $this->user());

        return view('livewire.client.diagnosis.place-material', [
            'material' => $this->ownMaterial($this->materialUuid),
            'eligible' => $eligible,
            'excluded' => $occasions->excludedProcesses($this->main()),
            'latest' => $occasions->latestCalendarOccasion($eligible),
        ])->layout('layouts::client', [
            'title' => __('processes.occasions.where_title'),
            'back' => $this->contributeUrl(),
            'backLabel' => __('processes.occasions.contribute_title'),
            'color' => 'cream',
        ])->title(__('processes.occasions.where_title'));
    }
}
