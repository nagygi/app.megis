<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Enums\ParticipantRole;
use App\Enums\StepMark;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Livewire\Concerns\WithDictation;
use App\Models\CapabilityStory;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Capabilities\CapabilityStoryService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Képességek (NM-12), the leader's step after the Megélés and the peer's
 * after T4: situations told to the values, typed or spoken. The user does
 * not score; the capability measurement reads the situations. Three cards
 * in a Teljes, one in a snapshot and for a peer; "Kihagyom" also closes it.
 */
class Capabilities extends Component
{
    use InteractsWithMainDiagnosis, WithDictation, WithFileUploads;

    /**
     * @var array<int, string> story id => answer
     */
    public array $answers = [];

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);

        $steps = $this->isLeader() ? $this->main()->leaderSteps() : $this->main()->peerSteps();
        abort_unless(in_array(DiagnosisStep::Capabilities, $steps, true), 404);

        if (! $this->ensurePeerStarted()) {
            $this->redirect($this->overviewUrl(), navigate: false);

            return;
        }

        $this->loadAnswers();
    }

    public function updatedAnswers(mixed $value, string $key): void
    {
        if (! $this->acceptsInput()) {
            return;
        }

        $story = $this->story((int) $key);
        $this->validateOnly('answers.'.$story->id, ['answers.'.$story->id => ['nullable', 'string', 'max:3000']], attributes: [
            'answers.'.$story->id => $story->value_name,
        ]);

        app(CapabilityStoryService::class)->save($story, (string) $value);
        $this->dispatch('saved');
    }

    public function changeValue(int $storyId, string $key, CapabilityStoryService $stories): void
    {
        $this->authorizeInput();

        $stories->changeValue($this->story($storyId), $key);
        $this->dispatch('saved');
    }

    /**
     * "+ Még egy helyzet" (at most five).
     */
    public function addStory(CapabilityStoryService $stories): void
    {
        $this->authorizeInput();

        $stories->add($this->main(), $this->user());
        $this->loadAnswers();
    }

    /**
     * ×: one card always stays.
     */
    public function removeStory(int $storyId, CapabilityStoryService $stories): void
    {
        $this->authorizeInput();

        if ($this->stories()->count() <= 1) {
            return;
        }

        $stories->remove($this->story($storyId));
        unset($this->answers[$storyId]);
    }

    /**
     * "Tovább": at least one situation, told in a few sentences.
     */
    public function submit(CapabilityStoryService $stories): void
    {
        $this->authorizeInput();
        abort_unless($this->ensurePeerStarted(), 403);
        $this->resetErrorBag();

        foreach ($this->stories() as $story) {
            $stories->save($story, (string) ($this->answers[$story->id] ?? ''));
        }

        if ($this->stories()->filter(fn (CapabilityStory $story) => $story->hasAnswer())->isEmpty()) {
            throw ValidationException::withMessages(['answers' => __('capabilities.step.one_needed', ['count' => (int) config('diagnosis.capability_stories.min_characters')])]);
        }

        $stories->submit($this->main(), $this->user(), ParticipantRole::from($this->role));
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
        $stories = app(CapabilityStoryService::class);
        $role = ParticipantRole::from($this->role);

        return view('livewire.client.diagnosis.capabilities', [
            'stories' => $this->stories(),
            'values' => $stories->valuesFor($this->main(), $this->user()),
            'suggested' => $stories->suggestedCount($this->main(), $role),
            'max' => (int) config('diagnosis.capability_stories.max'),
            'minCharacters' => (int) config('diagnosis.capability_stories.min_characters'),
            'editable' => $this->acceptsInput(),
            'isPeer' => $role === ParticipantRole::Peer,
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::Capabilities))->title(DiagnosisStep::Capabilities->label());
    }

    protected function canDictate(string $field): bool
    {
        if (! $this->acceptsInput() || $this->isImpersonating() || preg_match('/^answers\.(\d+)$/', $field, $match) !== 1) {
            return false;
        }

        return $this->stories()->contains('id', (int) $match[1]);
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->organization;
    }

    protected function dictated(string $field): void
    {
        $story = $this->story((int) substr($field, strlen('answers.')));
        app(CapabilityStoryService::class)->save($story, (string) $this->answers[$story->id]);
    }

    /**
     * @return Collection<int, CapabilityStory>
     */
    private function stories(): Collection
    {
        return app(CapabilityStoryService::class)->storiesFor($this->main(), $this->user(), ParticipantRole::from($this->role));
    }

    private function story(int $id): CapabilityStory
    {
        return $this->main()->capabilityStories()->where('user_id', $this->user()->id)->findOrFail($id);
    }

    private function loadAnswers(): void
    {
        $this->answers = $this->stories()->mapWithKeys(fn (CapabilityStory $story) => [$story->id => (string) $story->answer])->all();
    }
}
