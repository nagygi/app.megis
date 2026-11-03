<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Enums\ParticipantRole;
use App\Enums\StepMark;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Livewire\Concerns\WithDictation;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Capabilities\CapabilityStoryService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Képességek (NM-12), the leader's step after the Megélés and the peer's
 * after T4: recent situations in one text, typed, spoken or uploaded. The
 * user does not score and sees no capability; the capability measurement
 * reads the text against the Képességtár. "Kihagyom" also closes the step.
 */
class Capabilities extends Component
{
    use InteractsWithMainDiagnosis, WithDictation, WithFileUploads;

    public string $answer = '';

    public function mount(Organization $organization, Diagnosis $diagnosis, CapabilityStoryService $stories): void
    {
        $this->bootDiagnosis($organization, $diagnosis);

        $steps = $this->isLeader() ? $this->main()->leaderSteps() : $this->main()->peerSteps();
        abort_unless(in_array(DiagnosisStep::Capabilities, $steps, true), 404);

        if (! $this->ensurePeerStarted()) {
            $this->redirect($this->overviewUrl(), navigate: false);

            return;
        }

        $this->answer = (string) $stories->storyFor($this->main(), $this->user())?->answer;
    }

    public function updatedAnswer(mixed $value): void
    {
        if (! $this->acceptsInput()) {
            return;
        }

        $this->validateOnly('answer', ['answer' => ['nullable', 'string', 'max:'.$this->maxCharacters()]], attributes: [
            'answer' => __('capabilities.step.label'),
        ]);

        app(CapabilityStoryService::class)->save($this->main(), $this->user(), (string) $value);
        $this->dispatch('saved');
    }

    /**
     * "Tovább": a text of a few sentences; an emptied one is taken back first.
     */
    public function submit(CapabilityStoryService $stories): void
    {
        $this->authorizeInput();
        abort_unless($this->ensurePeerStarted(), 403);
        $this->resetErrorBag();
        $this->validate(['answer' => ['nullable', 'string', 'max:'.$this->maxCharacters()]], attributes: [
            'answer' => __('capabilities.step.label'),
        ]);

        $stories->save($this->main(), $this->user(), $this->answer);

        if (! $stories->submit($this->main(), $this->user(), ParticipantRole::from($this->role))) {
            throw ValidationException::withMessages(['answer' => __('capabilities.step.one_needed', ['count' => (int) config('diagnosis.capability_stories.min_characters')])]);
        }

        $this->redirect($this->nextUrl(DiagnosisStep::Capabilities), navigate: false);
    }

    /**
     * "Kihagyom": the step counts as done and can be made up until the reading (17 · 17.5).
     */
    public function skip(): void
    {
        $this->authorizeInput();
        abort_unless($this->ensurePeerStarted(), 403);

        $this->markStep(DiagnosisStep::Capabilities, StepMark::Skipped);
        $this->redirect($this->nextUrl(DiagnosisStep::Capabilities), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.client.diagnosis.capabilities', [
            'maxCharacters' => $this->maxCharacters(),
            'minCharacters' => (int) config('diagnosis.capability_stories.min_characters'),
            'editable' => $this->acceptsInput(),
            'isPeer' => ParticipantRole::from($this->role) === ParticipantRole::Peer,
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::Capabilities, __('capabilities.step.question')))->title(DiagnosisStep::Capabilities->label());
    }

    protected function canDictate(string $field): bool
    {
        return $field === 'answer' && $this->acceptsInput() && ! $this->isImpersonating();
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->organization;
    }

    protected function dictationLimit(string $field): ?int
    {
        return $this->maxCharacters();
    }

    protected function dictated(string $field): void
    {
        app(CapabilityStoryService::class)->save($this->main(), $this->user(), $this->answer);
    }

    private function maxCharacters(): int
    {
        return (int) config('diagnosis.capability_stories.max_characters');
    }
}
