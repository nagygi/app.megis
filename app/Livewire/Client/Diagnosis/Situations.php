<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\MaterialContext;
use App\Livewire\Client\Diagnosis\Concerns\HandlesOccasionMaterials;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\OccasionExperience;
use App\Models\Organization;
use App\Services\Materials\MaterialStore;
use Illuminate\View\View;
use Livewire\Component;

/**
 * T5·b · A helyzeteid (12 · 12.5): the user's own placed materials in time
 * order, with or without an experience; the same page for leader and peer.
 * The own recording can be deleted (06 · 6.6): the transcript stays.
 */
class Situations extends Component
{
    use HandlesOccasionMaterials, InteractsWithMainDiagnosis;

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->ensurePeerStarted(), 403);
    }

    public function deleteAudio(string $uuid, MaterialStore $materials): void
    {
        abort_if($this->isImpersonating(), 403, __('impersonation.forbidden'));

        $materials->deleteAudio($this->ownMaterial($uuid));
        session()->flash('status', __('client.recordings.deleted'));
    }

    public function render(): View
    {
        $materials = Material::query()
            ->where('parent_diagnosis_id', $this->diagnosis->id)
            ->where('user_id', $this->user()->id)
            ->where('context', MaterialContext::Occasion)
            ->whereNotNull('placed_at')
            ->with('occasion.process')
            ->get()
            ->sortByDesc(fn (Material $material) => $material->occasion?->occurred_at ?? $material->placed_at)
            ->values();

        return view('livewire.client.diagnosis.situations', [
            'materials' => $materials,
            'experiences' => OccasionExperience::query()
                ->where('user_id', $this->user()->id)
                ->whereIn('process_occasion_id', $materials->pluck('process_occasion_id')->filter())
                ->get()
                ->keyBy('process_occasion_id'),
            'editable' => $this->acceptsInput(),
            'isImpersonating' => $this->isImpersonating(),
        ])->layout('layouts::client', [
            'title' => __('processes.occasions.situations_title'),
            'back' => $this->overviewUrl(),
            'backLabel' => __('diagnosis.home_back'),
            'color' => 'cream',
        ])->title(__('processes.occasions.situations_title'));
    }
}
