<?php

namespace App\Livewire\Client\Diagnosis;

use App\Livewire\Client\Diagnosis\Concerns\HandlesOccasionMaterials;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\SupportQuestion;
use App\Services\Processes\OccasionService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * "Egy helyzet" (12 · 12.3): a short account of an occasion, spoken with the
 * Értéktükör recorder or typed; then "Hova tartozik?". No consent is needed:
 * it is the user's own account, not a recording of the meeting.
 */
class Situation extends Component
{
    use HandlesOccasionMaterials, InteractsWithMainDiagnosis, WithFileUploads;

    public string $mode = 'record';

    public string $text = '';

    /** @var TemporaryUploadedFile|null */
    public $recording = null;

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        $this->authorizeContribution();
        // The user's own voice, never recorded in their name (14 · 14.6).
        abort_if($this->isImpersonating(), 403, __('impersonation.forbidden'));
    }

    public function setMode(string $mode): void
    {
        if (in_array($mode, ['record', 'text'], true)) {
            $this->mode = $mode;
            $this->resetValidation();
        }
    }

    public function submitRecording(int $seconds, OccasionService $occasions): void
    {
        $this->authorizeContribution();
        $this->validate([
            'recording' => ['required', 'file', 'max:51200', 'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/mpeg,audio/wav,audio/x-wav'],
        ], ['recording.required' => __('mirror.recording_missing')], ['recording' => __('mirror.mode_record')]);

        $this->placeNext($occasions->situationRecording($this->main(), $this->user(), $this->recording, $seconds));
    }

    public function submitText(OccasionService $occasions): void
    {
        $this->authorizeContribution();
        $this->validate(
            ['text' => ['required', 'string', 'min:20', 'max:'.config('megis.materials.text_max_characters')]],
            attributes: ['text' => __('processes.occasions.situation_text')],
        );

        $this->placeNext($occasions->situationText($this->main(), $this->user(), $this->text));
    }

    public function render(): View
    {
        return view('livewire.client.diagnosis.situation', [
            'questions' => SupportQuestion::query()->forRecording()->pluck('text')->all(),
        ])->layout('layouts::client', [
            'title' => __('processes.occasions.situation'),
            'back' => $this->contributeUrl(),
            'backLabel' => __('processes.occasions.contribute_title'),
            'color' => 'cream',
        ])->title(__('processes.occasions.situation'));
    }

    private function placeNext(Material $material): void
    {
        $this->redirect(route('client.diagnosis.place', [$this->organization, $this->diagnosis, $material]), navigate: false);
    }
}
