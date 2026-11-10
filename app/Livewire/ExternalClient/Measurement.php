<?php

namespace App\Livewire\ExternalClient;

use App\Enums\ExternalClientItemKind;
use App\Enums\MainDiagnosisStage;
use App\Livewire\Concerns\WithDictation;
use App\Models\Diagnosis;
use App\Models\ExternalClient;
use App\Models\ExternalClientItem;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use App\Services\ExternalClients\ExternalClientMeasurementService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The Ügyfélmérés of a client user (35 · 35.6): the consent, then one
 * question per screen (the one for the values, then one per process), told,
 * typed or uploaded (Leírom · Elmondom · Feltöltöm), an overview and
 * "Beküldöm". While the diagnosis runs the answers can be changed and sent
 * again; after it, the page says it closed.
 */
class Measurement extends Component
{
    use WithDictation, WithFileUploads;

    /**
     * The longest answer; a longer dictation or file is condensed to it.
     */
    public const MAX_CHARACTERS = 5000;

    #[Locked]
    public Diagnosis $diagnosis;

    /**
     * intro · question · summary · sent
     */
    public string $screen = 'intro';

    public int $position = 0;

    public bool $consent = false;

    /**
     * The answers by question key: "values", or a process item's id.
     *
     * @var array<string, string>
     */
    public array $answers = [];

    public function mount(Diagnosis $diagnosis, ExternalClientMeasurementService $measurements): void
    {
        $this->diagnosis = $diagnosis;
        $main = $this->main();

        abort_if($main === null || $main->stage->isBeforeRunning(), 404);
        abort_if($this->client() === null, 403);

        $saved = $measurements->answersFor($main, $this->user());

        foreach ($this->questions() as $question) {
            $this->answers[$question['key']] = (string) $saved->get($question['key'])?->answer;
        }

        if ($measurements->hasConsent($main, $this->user())) {
            $this->screen = $saved->whereNotNull('submitted_at')->isNotEmpty() ? 'sent' : 'question';
        }
    }

    /**
     * "Kezdem": the consent is the user's own act, never in their name (14 · 14.6).
     */
    public function start(ExternalClientMeasurementService $measurements): void
    {
        $this->authorizeInput();
        abort_if(app(ImpersonationService::class)->isImpersonating(), 403, __('impersonation.forbidden'));

        $this->validate(['consent' => ['accepted']], ['consent.accepted' => __('external_clients.measurement.consent_required')]);

        $measurements->giveConsent($this->main(), $this->user());
        $this->screen = 'question';
        $this->position = 0;
    }

    public function updatedAnswers(mixed $value, string $key): void
    {
        if (! $this->canAnswer()) {
            return;
        }

        $this->validateOnly('answers.'.$key, $this->answerRules());
        $this->saveDraft($key);
    }

    public function next(): void
    {
        $this->authorizeAnswering();
        $this->saveCurrent();

        if ($this->position >= $this->questions()->count() - 1) {
            $this->screen = 'summary';

            return;
        }

        $this->position++;
    }

    public function previous(): void
    {
        $this->authorizeAnswering();
        $this->saveCurrent();
        $this->position = max(0, $this->position - 1);
    }

    /**
     * "Módosítom" from the overview.
     */
    public function edit(int $index): void
    {
        $this->authorizeAnswering();
        $this->position = max(0, min($index, $this->questions()->count() - 1));
        $this->screen = 'question';
    }

    public function summary(): void
    {
        $this->authorizeAnswering();
        $this->saveCurrent();
        $this->screen = 'summary';
    }

    /**
     * "Beküldöm": at least one answer; the emptied ones are taken back.
     */
    public function submit(ExternalClientMeasurementService $measurements): void
    {
        $this->authorizeAnswering();
        $this->validate($this->answerRules());

        foreach ($this->questions() as $question) {
            $this->saveDraft($question['key']);
        }

        if (collect($this->answers)->filter(fn ($answer) => trim((string) $answer) !== '')->isEmpty()) {
            throw ValidationException::withMessages(['answers' => __('external_clients.measurement.nothing_to_submit')]);
        }

        $measurements->submit($this->main(), $this->user());
        $this->screen = 'sent';
    }

    public function render(): View
    {
        $main = $this->main();
        $questions = $this->questions();
        $organization = $main->diagnosis->organization;

        return view('livewire.external-client.measurement', [
            'organization' => $organization,
            'questions' => $questions,
            'current' => $questions->get($this->position),
            'open' => $this->acceptsInput(),
            'deadline' => $main->readingStartsOn()->copy()->subDay()->format('Y.m.d.'),
            'maxCharacters' => self::MAX_CHARACTERS,
        ])->layout('layouts::client', [
            'title' => __('external_clients.measurement.title'),
            'subtitle' => $organization->name,
            'color' => 'white',
        ])->title(__('external_clients.measurement.title'));
    }

    protected function canDictate(string $field): bool
    {
        return str_starts_with($field, 'answers.')
            && $this->canAnswer()
            && $this->questions()->contains('key', mb_substr($field, mb_strlen('answers.')));
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->main()?->diagnosis->organization;
    }

    protected function dictationLimit(string $field): ?int
    {
        return self::MAX_CHARACTERS;
    }

    protected function dictated(string $field): void
    {
        $this->saveDraft(mb_substr($field, mb_strlen('answers.')));
    }

    private function main(): ?MainDiagnosis
    {
        return $this->diagnosis->main;
    }

    private function user(): User
    {
        return auth('web')->user();
    }

    private function client(): ?ExternalClient
    {
        $main = $this->main();

        return $main === null ? null : app(ExternalClientMeasurementService::class)->clientFor($main, $this->user());
    }

    /**
     * @return Collection<int, array{key: string, kind: ExternalClientItemKind, question: string, process: ?string, item: ?ExternalClientItem}>
     */
    private function questions(): Collection
    {
        $client = $this->client();

        return $client === null ? collect() : app(ExternalClientMeasurementService::class)->questionsFor($this->main(), $client);
    }

    /**
     * Answers are taken while the diagnosis runs (35 · 35.6).
     */
    private function acceptsInput(): bool
    {
        return $this->main()?->stage === MainDiagnosisStage::Running && $this->client() !== null;
    }

    private function canAnswer(): bool
    {
        return $this->acceptsInput() && $this->screen !== 'intro'
            && app(ExternalClientMeasurementService::class)->hasConsent($this->main(), $this->user());
    }

    private function authorizeInput(): void
    {
        abort_unless($this->acceptsInput(), 403);
    }

    private function authorizeAnswering(): void
    {
        abort_unless($this->canAnswer(), 403);
    }

    private function saveCurrent(): void
    {
        $question = $this->questions()->get($this->position);

        if ($question !== null) {
            $this->validateOnly('answers.'.$question['key'], $this->answerRules());
            $this->saveDraft($question['key']);
        }
    }

    private function saveDraft(string $key): void
    {
        $question = $this->questions()->firstWhere('key', $key);

        if ($question === null) {
            return;
        }

        app(ExternalClientMeasurementService::class)->saveDraft($this->main(), $this->client(), $this->user(), $question, trim((string) ($this->answers[$key] ?? '')));
    }

    /**
     * @return array<string, list<string>>
     */
    private function answerRules(): array
    {
        return ['answers.*' => ['nullable', 'string', 'max:'.self::MAX_CHARACTERS]];
    }
}
