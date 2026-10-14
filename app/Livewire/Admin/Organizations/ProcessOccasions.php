<?php

namespace App\Livewire\Admin\Organizations;

use App\Exceptions\UnreadableMaterialException;
use App\Models\Organization;
use App\Models\Process;
use App\Models\ProcessOccasion;
use App\Services\Materials\MaterialStore;
use App\Services\Processes\OccasionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The occasions of one process (12 · 12.6): date, materials, uploader, state
 * and the number of experiences; the transcript opens, the audio plays.
 * "+ Anyag": the admin adds a material with the same rules.
 */
class ProcessOccasions extends Component
{
    use WithFileUploads;

    public Organization $organization;

    public Process $process;

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public string $date = '';

    public bool $consent = false;

    public function mount(Organization $organization, Process $process): void
    {
        Gate::authorize('update', $organization);
        abort_unless($process->organization_id === $organization->id, 404);

        $this->organization = $organization;
        $this->process = $process;
        $this->date = today()->toDateString();
    }

    public function upload(OccasionService $occasions): void
    {
        Gate::authorize('update', $this->organization);
        $main = $occasions->adminDiagnosisFor($this->process);
        abort_if($main === null, 403);

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
            'date' => ['required', 'date', 'before_or_equal:today'],
            'consent' => ['accepted'],
        ], ['consent.accepted' => __('processes.occasions.consent_required')], [
            'file' => __('processes.occasions.file'),
            'date' => __('processes.occasions.when'),
        ]);

        try {
            $occasions->adminUpload($main, $this->process, $this->date, $this->file, auth('admin')->user(), $this->consent);
        } catch (UnreadableMaterialException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $this->reset(['file', 'consent']);
        session()->flash('status', __('admin.occasions.uploaded'));
    }

    public function render(OccasionService $occasions): View
    {
        $processIds = [$this->process->id, ...$this->process->children()->pluck('id')->all()];

        return view('livewire.admin.organizations.process-occasions', [
            'occasions' => ProcessOccasion::query()
                ->whereIn('process_id', $processIds)
                ->with(['process', 'materials.user', 'materials.creator'])
                ->withCount(['experiences' => fn ($query) => $query->whereNotNull('submitted_at')])
                ->latest('occurred_at')
                ->get(),
            'main' => $occasions->adminDiagnosisFor($this->process),
            'rules' => MaterialStore::rules(),
        ])->layout('layouts::admin', ['organization' => $this->organization])->title($this->process->name);
    }
}
