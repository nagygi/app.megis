<?php

namespace App\Livewire\ExternalClient;

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
 * question per screen, told, typed or uploaded (Leírom · Elmondom ·
 * Feltöltöm), an overview and "Beküldöm". While the diagnosis runs the
 * answers can be changed and sent again; after it, the page says it closed.
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
     * The answers by item id.
     *
     * @var array<int, string>
     */
    public array $answers = [];

    public function mount(Diagnosis $diagnosis, ExternalClientMeasurementService $measurements): void
    {
        $this->diagnosis = $diagnosis;
        $main = $this->main();

        abort_if($main === null || $main->stage->isBeforeRunning(), 404);
        abort_if($this->client() === null, 403);

        $saved = $measurements->answersFor($main, $this->user());

        foreach ($this->items() as $item) {
            $this->answers[$item->id] = (string) $saved->get($item->id)?->answer;
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
        $this->saveDraft((int) $key);
    }

    public function next(): void
    {
        $this->authorizeAnswering();
        $this->saveCurrent();

        if ($this->position >= $this->items()->count() - 1) {
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
        $this->position = max(0, min($index, $this->items()->count() - 1));
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

        foreach ($this->items() as $item) {
            $this->saveDraft($item->id);
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
        $items = $this->items();
        $organization = $main->diagnosis->organization;

        return view('livewire.external-client.measurement', [
            'organization' => $organization,
            'items' => $items,
            'current' => $items->get($this->position),
            'valueNames' => app(ExternalClientMeasurementService::class)->valueNames($main),
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
        return $this->canAnswer() && $this->items()->contains('id', (int) str_replace('answers.', '', $field)) && str_starts_with($field, 'answers.');
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
        $this->saveDraft((int) str_replace('answers.', '', $field));
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
     * @return Collection<int, ExternalClientItem>
     */
    private function items(): Collection
    {
        $client = $this->client();

        return $client === null ? collect() : app(ExternalClientMeasurementService::class)->itemsFor($this->main(), $client);
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
        $item = $this->items()->get($this->position);

        if ($item !== null) {
            $this->validateOnly('answers.'.$item->id, $this->answerRules());
            $this->saveDraft($item->id);
        }
    }

    private function saveDraft(int $itemId): void
    {
        $item = $this->items()->firstWhere('id', $itemId);

        if ($item === null) {
            return;
        }

        app(ExternalClientMeasurementService::class)->saveDraft($this->main(), $this->client(), $this->user(), $item, trim((string) ($this->answers[$itemId] ?? '')));
    }

    /**
     * @return array<string, list<string>>
     */
    private function answerRules(): array
    {
        return ['answers.*' => ['nullable', 'string', 'max:'.self::MAX_CHARACTERS]];
    }
}
