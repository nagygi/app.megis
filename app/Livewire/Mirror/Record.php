<?php

namespace App\Livewire\Mirror;

use App\Enums\MaterialContext;
use App\Enums\MaterialSource;
use App\Exceptions\UnreadableMaterialException;
use App\Livewire\Concerns\ChecksTurnstile;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\SupportQuestion;
use App\Models\User;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\Materials\MaterialStore;
use App\Services\Mirror\MirrorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * ET2 · the recording (06 · 6.1): spoken in the browser, typed, or a file.
 * Public, it is protected by Turnstile and an IP limit; from the own menu
 * (06 · 6.2) it belongs to the signed-in user and the active organization.
 */
class Record extends Component
{
    use ChecksTurnstile;
    use WithFileUploads;

    public ?Organization $organization = null;

    public string $mode = 'record';

    public string $text = '';

    /** @var TemporaryUploadedFile|null */
    public $recording = null;

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    /**
     * The main diagnosis the mirror belongs to (17 · 17.4, 17.6).
     */
    #[Url(as: 'diagnozis')]
    public ?int $mainDiagnosisId = null;

    public function mount(?Organization $organization = null): void
    {
        $this->organization = $organization?->exists ? $organization : null;

        $options = $this->diagnosisOptions();

        // One running diagnosis needs no question; a link from a step names its own.
        if (! $options->contains(fn (MainDiagnosis $main) => $main->diagnosis_id === $this->mainDiagnosisId)) {
            $this->mainDiagnosisId = $options->count() === 1 ? $options->first()->diagnosis_id : null;
        }
    }

    public function setMode(string $mode): void
    {
        if (in_array($mode, ['record', 'text', 'file'], true)) {
            $this->mode = $mode;
            $this->resetValidation();
        }
    }

    public function submitRecording(int $seconds, MaterialStore $materials, MirrorService $mirrors): void
    {
        $this->validate([
            'recording' => [
                'required',
                'file',
                'max:51200',
                'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/mpeg,audio/wav,audio/x-wav',
            ],
        ], ['recording.required' => __('mirror.recording_missing')], ['recording' => __('mirror.mode_record')]);
        $this->guard();

        $material = $materials->fromAudio($this->recording, $this->context(), MaterialSource::Recording, $this->user(), $this->organization);

        // MediaRecorder files carry no duration; without ffprobe the browser's count is kept.
        if ($material->audio_seconds === null) {
            $material->update(['audio_seconds' => max(0, min($seconds, (int) config('megis.materials.recording_max_seconds')))]);
        }

        $this->begin($material, $mirrors);
    }

    public function submitText(MaterialStore $materials, MirrorService $mirrors): void
    {
        $this->validate(
            ['text' => ['required', 'string', 'min:50', 'max:'.config('megis.materials.text_max_characters')]],
            attributes: ['text' => __('mirror.text_label')],
        );
        $this->guard();

        $this->begin($materials->fromText($this->text, $this->context(), $this->user(), $this->organization), $mirrors);
    }

    public function submitFile(MaterialStore $materials, MirrorService $mirrors): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:'.config('megis.materials.text_max_kilobytes'), 'extensions:'.implode(',', config('megis.materials.text_extensions'))],
        ], attributes: ['file' => __('mirror.file_label')]);
        $this->guard();

        try {
            $material = $materials->fromTextFile($this->file, $this->context(), $this->user(), $this->organization);
        } catch (UnreadableMaterialException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $this->begin($material, $mirrors);
    }

    public function render(): View
    {
        $view = view('livewire.mirror.record', [
            'questions' => SupportQuestion::query()->forRecording()->pluck('text')->all(),
            'textRules' => ['formats' => implode(', ', config('megis.materials.text_extensions')), 'size' => (int) (config('megis.materials.text_max_kilobytes') / 1024)],
            'isPublic' => $this->organization === null,
            'diagnosisOptions' => $this->diagnosisOptions(),
        ]);

        return $this->organization === null
            ? $view->layout('layouts::public', ['title' => __('mirror.title'), 'description' => __('mirror.meta_description')])
            : $view->layout('layouts::client', ['title' => __('mirror.title'), 'back' => route('client.home', $this->organization), 'backLabel' => $this->organization->name]);
    }

    /**
     * Public submissions: Turnstile and at most a few per IP address and hour.
     */
    private function guard(): void
    {
        if ($this->organization !== null) {
            $this->ensureDiagnosisChosen();

            return;
        }

        $this->checkTurnstile();

        $key = 'mirror-submit:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, (int) config('megis.mirror.submissions_per_hour'))) {
            throw ValidationException::withMessages(['turnstileToken' => __('mirror.throttled')]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function begin(Material $material, MirrorService $mirrors): void
    {
        if ($this->mainDiagnosisId !== null) {
            $material->update(['parent_diagnosis_id' => $this->mainDiagnosisId]);
        }

        $session = $mirrors->start($material, $this->user(), $this->organization);
        session()->push('mirror_tokens', $session->token);

        $this->redirect($this->organization === null
            ? route('mirror.result', $session)
            : route('client.mirror.result', [$this->organization, $session]), navigate: false);
    }

    /**
     * With more running diagnoses the user says which one the mirror belongs to (17 · 17.6).
     */
    private function ensureDiagnosisChosen(): void
    {
        $options = $this->diagnosisOptions();

        if ($options->contains(fn (MainDiagnosis $main) => $main->diagnosis_id === $this->mainDiagnosisId)) {
            return;
        }

        if ($options->count() > 1) {
            throw ValidationException::withMessages(['mainDiagnosisId' => __('mirror.which_diagnosis_required')]);
        }

        // Only a diagnosis the user takes part in, never one typed into the request.
        $this->mainDiagnosisId = $options->first()?->diagnosis_id;
    }

    /**
     * @return Collection<int, MainDiagnosis>
     */
    private function diagnosisOptions(): Collection
    {
        $user = $this->user();

        if ($this->organization === null || $user === null) {
            return collect();
        }

        return app(MainDiagnosisService::class)->acceptingInputFor($user, $this->organization);
    }

    private function context(): MaterialContext
    {
        return $this->organization === null ? MaterialContext::PublicMirror : MaterialContext::Mirror;
    }

    private function user(): ?User
    {
        return $this->organization === null ? null : auth('web')->user();
    }
}
