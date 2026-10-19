<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Exceptions\UnreadableMaterialException;
use App\Livewire\Client\Diagnosis\Concerns\HandlesOccasionMaterials;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\CalendarConnection;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\OccasionExperience;
use App\Models\Organization;
use App\Services\Materials\MaterialStore;
use App\Services\Processes\OccasionService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * V11 · Hozzáteszek (12 · 12.1): an Értéktükör, a situation, or an upload in
 * one box; below, the user's own materials with their state.
 */
class Contribute extends Component
{
    use HandlesOccasionMaterials, InteractsWithMainDiagnosis, WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->ensurePeerStarted(), 403);
    }

    public function storeUpload(OccasionService $occasions): void
    {
        $this->authorizeContribution();

        $audio = config('megis.materials.audio_extensions');
        $text = config('megis.materials.text_extensions');
        $isAudio = $this->file instanceof TemporaryUploadedFile && in_array(strtolower($this->file->getClientOriginalExtension()), $audio, true);

        $this->validate([
            'file' => [
                'required',
                'file',
                'extensions:'.implode(',', [...$audio, ...$text]),
                'max:'.($isAudio ? config('megis.materials.audio_max_kilobytes') : config('megis.materials.text_max_kilobytes')),
            ],
        ], attributes: ['file' => __('processes.occasions.file')]);

        try {
            $material = $occasions->upload($this->main(), $this->user(), $this->file);
        } catch (UnreadableMaterialException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $this->redirect(route('client.diagnosis.place', [$this->organization, $this->diagnosis, $material]), navigate: false);
    }

    public function retry(string $uuid, OccasionService $occasions): void
    {
        $this->authorizeContribution();

        $occasions->retry($this->ownMaterial($uuid));
    }

    public function remove(string $uuid, OccasionService $occasions): void
    {
        $this->authorizeContribution();

        $occasions->remove($this->ownMaterial($uuid));
        session()->flash('status', __('processes.occasions.removed'));
    }

    public function render(): View
    {
        $materials = Material::query()
            ->where('parent_diagnosis_id', $this->diagnosis->id)
            ->where('user_id', $this->user()->id)
            ->where('context', MaterialContext::Occasion)
            ->with('occasion.process')
            ->latest('id')
            ->get();
        $experiences = OccasionExperience::query()
            ->where('user_id', $this->user()->id)
            ->whereIn('process_occasion_id', $materials->pluck('process_occasion_id')->filter())
            ->get()
            ->keyBy('process_occasion_id');

        // "Feldolgozás alatt" lists what is still open: placing and the
        // experience can be finished later; finished ones are on T5·b (V11).
        $openMaterials = $materials
            ->map(fn (Material $material) => ['material' => $material, 'state' => $this->materialState($material, $experiences)])
            ->reject(fn (array $entry) => $entry['state'] === 'done')
            ->values();

        return view('livewire.client.diagnosis.contribute', [
            'materials' => $materials,
            'openMaterials' => $openMaterials,
            'canContribute' => app(OccasionService::class)->canContribute($this->main(), $this->user()),
            'rules' => MaterialStore::rules(),
            'connection' => $this->isLeader()
                ? CalendarConnection::query()->whereBelongsTo($this->organization)->where('user_id', $this->user()->id)->where('status', '!=', CalendarConnection::DISCONNECTED)->latest('id')->first()
                : null,
            'canRecordMirror' => ! $this->isImpersonating(),
        ])->layout('layouts::client', [
            'title' => __('processes.occasions.contribute_title'),
            'back' => $this->overviewUrl(),
            'backLabel' => __('diagnosis.home_back'),
            'color' => 'cream',
        ])->title(__('processes.occasions.contribute_title'));
    }

    /**
     * @param  Collection<int, OccasionExperience>  $experiences
     */
    private function materialState(Material $material, Collection $experiences): string
    {
        return match (true) {
            $material->status === MaterialStatus::Failed => 'failed',
            $material->placed_at === null => 'unplaced',
            $material->status->isWorking() => 'processing',
            $material->isUnclassified() => 'unclassified',
            $material->process_occasion_id !== null && ! $experiences->has($material->process_occasion_id) => 'experience_open',
            default => 'done',
        };
    }
}
