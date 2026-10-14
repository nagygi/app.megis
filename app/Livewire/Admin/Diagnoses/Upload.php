<?php

namespace App\Livewire\Admin\Diagnoses;

use App\Enums\MaterialContext;
use App\Enums\MaterialSource;
use App\Exceptions\UnreadableMaterialException;
use App\Models\DiagnosisType;
use App\Models\Material;
use App\Models\Organization;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Diagnózisok → „+ Feltöltés” (06 · 6.3): an audio file or a transcript for
 * one or more users of an organization. Speakers are assigned on the next page.
 */
class Upload extends Component
{
    use WithFileUploads;

    public ?int $organization_id = null;

    public ?int $diagnosis_type_id = null;

    public string $diagnosed_on = '';

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public function mount(): void
    {
        Gate::authorize('create', Material::class);

        $this->diagnosed_on = today()->toDateString();
        $this->diagnosis_type_id = DiagnosisType::query()->where('key', 'workshop')->value('id');
    }

    public function save(MaterialStore $materials, MaterialPipeline $pipeline): void
    {
        Gate::authorize('create', Material::class);

        $audio = config('megis.materials.audio_extensions');
        $text = config('megis.materials.text_extensions');
        $extension = $this->file instanceof TemporaryUploadedFile ? strtolower($this->file->getClientOriginalExtension()) : '';
        $isAudio = in_array($extension, $audio, true);

        $validated = $this->validate([
            'organization_id' => ['required', Rule::exists('organizations', 'id')->whereNull('deactivated_at')],
            'diagnosis_type_id' => ['required', Rule::exists('diagnosis_types', 'id')],
            'diagnosed_on' => ['required', 'date'],
            'file' => [
                'required',
                'file',
                'extensions:'.implode(',', [...$audio, ...$text]),
                'max:'.($isAudio ? config('megis.materials.audio_max_kilobytes') : config('megis.materials.text_max_kilobytes')),
            ],
        ], attributes: [
            'organization_id' => __('admin.diagnoses.organization'),
            'diagnosis_type_id' => __('admin.diagnoses.type'),
            'diagnosed_on' => __('admin.diagnoses.date'),
            'file' => __('admin.upload.file'),
        ]);

        $organization = Organization::query()->findOrFail($validated['organization_id']);

        try {
            $material = $isAudio
                ? $materials->fromAudio($this->file, MaterialContext::AdminUpload, MaterialSource::AudioUpload, null, $organization)
                : $materials->fromTextFile($this->file, MaterialContext::AdminUpload, null, $organization);

            if ($isAudio) {
                $materials->ensureAudioLength($material);
            }
        } catch (UnreadableMaterialException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $material->update([
            'diagnosis_type_id' => $validated['diagnosis_type_id'],
            'diagnosed_on' => $validated['diagnosed_on'],
        ]);
        $pipeline->start($material);

        $this->redirectRoute('admin.materials.show', $material, navigate: false);
    }

    public function render(): View
    {
        return view('livewire.admin.diagnoses.upload', [
            'organizations' => Organization::query()->whereNull('deactivated_at')->orderBy('name')->get(['id', 'name']),
            'types' => DiagnosisType::query()->orderBy('name')->get(['id', 'name']),
            'rules' => MaterialStore::rules(),
        ])->layout('layouts::admin')->title(__('admin.upload.title'));
    }
}
