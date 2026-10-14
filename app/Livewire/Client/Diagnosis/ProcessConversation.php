<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Livewire\Concerns\WithDictation;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Models\ProcessConversation as Conversation;
use App\Services\Processes\ConversationService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * V6·c · Beszélgetés (10): four questions in a fixed order, typed or
 * spoken; after each answer the AI extracts the processes into chips.
 * The answers are kept, the conversation can be continued (10 · 10.3).
 */
class ProcessConversation extends Component
{
    use InteractsWithMainDiagnosis, WithDictation, WithFileUploads;

    /**
     * @var array<int, string>
     */
    public array $answers = [1 => '', 2 => '', 3 => '', 4 => ''];

    public function mount(Organization $organization, Diagnosis $diagnosis, ConversationService $conversations): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->isLeader(), 403);

        $conversation = $conversations->forUser($organization, $this->user(), $this->main());

        foreach (Conversation::QUESTIONS as $question) {
            $this->answers[$question] = $conversation->answer($question);
        }
    }

    public function updatedAnswers(mixed $value, string $key): void
    {
        $conversations = app(ConversationService::class);
        $this->authorizeInput();
        $question = (int) $key;

        if (! in_array($question, Conversation::QUESTIONS, true)) {
            return;
        }

        $this->validateOnly('answers.'.$question, ['answers.'.$question => ['nullable', 'string', 'max:3000']], attributes: [
            'answers.'.$question => __('processes.conversation.answer', ['number' => $question]),
        ]);

        $conversations->answer($this->conversation(), $question, (string) $value);
        $this->dispatch('saved');
    }

    public function removeChip(int $question, int $index, ConversationService $conversations): void
    {
        $this->authorizeInput();

        $conversations->removeChip($this->conversation(), $question, $index);
    }

    /**
     * "Válasz és tovább": at least one answer; the chips become cards on V6·d (10 · 10.3).
     */
    public function finish(ConversationService $conversations): void
    {
        $this->authorizeInput();
        $conversation = $this->conversation();

        foreach (Conversation::QUESTIONS as $question) {
            $conversations->answer($conversation, $question, $this->answers[$question] ?? '');
        }

        $conversation->refresh();

        if (collect(Conversation::QUESTIONS)->every(fn (int $question) => $conversation->answer($question) === '')) {
            throw ValidationException::withMessages(['answers' => __('processes.conversation.one_needed')]);
        }

        $conversations->finish($conversation);
        $this->redirect(route('client.diagnosis.process-list', [$this->organization, $this->diagnosis]), navigate: false);
    }

    public function render(): View
    {
        $conversation = $this->conversation();
        $chips = collect(Conversation::QUESTIONS)->flatMap(fn (int $question) => collect($conversation->chips($question))
            ->map(fn (array $chip, int $index) => [...$chip, 'question' => $question, 'index' => $index]));

        return view('livewire.client.diagnosis.process-conversation', [
            'chips' => $chips,
            'processCount' => $chips->where('unclear', false)->count(),
            'unclearCount' => $chips->where('unclear', true)->count(),
            'extracting' => collect(Conversation::QUESTIONS)->contains(fn (int $question) => $conversation->isExtracting($question)),
            'editable' => $this->acceptsInput(),
        ])->layout('layouts::client', [
            ...$this->stepLayout(DiagnosisStep::Processes),
            'title' => __('processes.conversation.title'),
            'back' => route('client.diagnosis.folyamatok', [$this->organization, $this->diagnosis]),
            'backLabel' => DiagnosisStep::Processes->label(),
        ])->title(__('processes.conversation.title'));
    }

    protected function canDictate(string $field): bool
    {
        return $this->acceptsInput() && ! $this->isImpersonating() && preg_match('/^answers\.[1-4]$/', $field) === 1;
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->organization;
    }

    protected function dictated(string $field): void
    {
        $question = (int) substr($field, strlen('answers.'));
        app(ConversationService::class)->answer($this->conversation(), $question, $this->answers[$question]);
    }

    private function conversation(): Conversation
    {
        return app(ConversationService::class)->forUser($this->organization, $this->user(), $this->main());
    }
}
