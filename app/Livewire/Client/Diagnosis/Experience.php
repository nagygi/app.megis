<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Enums\MainDiagnosisSubject;
use App\Enums\TreeElement;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Livewire\Concerns\NotesImpersonatedChanges;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Models\Version;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * V5 · A megélésed (08 · 8.3) and the peer's T4 (25 · 25.4): one answer per
 * row, 1–6 or "nem tudom", "9 / 9 megválaszolva". The answers are kept while
 * the user goes; sending makes the Megélés subdiagnosis.
 */
class Experience extends Component
{
    use InteractsWithMainDiagnosis, NotesImpersonatedChanges;

    /**
     * @var array<string, string>
     */
    public array $answers = [];

    public function mount(Organization $organization, Diagnosis $diagnosis, ExperienceService $experiences): void
    {
        $this->bootDiagnosis($organization, $diagnosis);

        if (! $this->ensurePeerStarted()) {
            $this->redirect($this->overviewUrl(), navigate: false);

            return;
        }

        $this->answers = $experiences->answersFor($this->main(), $this->user(), $this->participant()->experience_draft);
    }

    public function updatedAnswers(): void
    {
        if (! $this->acceptsInput()) {
            return;
        }

        $allowed = array_column($this->rows(), 'key');
        $this->answers = array_filter(
            array_intersect_key($this->answers, array_flip($allowed)),
            fn ($answer) => in_array($answer, ['1', '2', '3', '4', '5', '6', ExperienceService::DONT_KNOW], true),
        );

        $this->participant()->update(['experience_draft' => $this->answers]);
        $this->noteChange('answers');
        $this->dispatch('saved');
    }

    /**
     * "Tovább": every row needs an answer (08 · 8.3).
     */
    public function submit(ExperienceService $experiences): void
    {
        $this->authorizeInput();
        abort_unless($this->ensurePeerStarted(), 403);
        $this->resetErrorBag();

        $version = $this->version(record: true);
        $rows = $experiences->rows($version->snapshot);
        $missing = array_filter($rows, fn (array $row) => ! isset($this->answers[$row['key']]));

        if ($missing !== []) {
            throw ValidationException::withMessages(['answers' => __('diagnosis.experience.missing', ['count' => count($missing)])]);
        }

        $experiences->submit($this->main(), $this->user(), $version, $this->answers);
        $this->participant()->update(['experience_draft' => null]);

        $this->redirect($this->nextUrl(DiagnosisStep::Experience), navigate: false);
    }

    public function render(): View
    {
        $rows = $this->rows();
        $individual = $this->main()->subject === MainDiagnosisSubject::Individual;
        // The brand base's questions as V4 asks them, in the singular for a private person (MEGIS-3).
        $brandTexts = $individual ? 'diagnosis.brand_individual' : 'diagnosis.brand';
        $treeRows = array_map(
            fn (array $row) => [...$row, 'question' => __($brandTexts.'.questions.'.(TreeElement::from($row['element'])->valueSetField() ?? 'root'))],
            array_values(array_filter($rows, fn (array $row) => $row['kind'] === 'tree_element')),
        );

        return view('livewire.client.diagnosis.experience', [
            'valueRows' => array_values(array_filter($rows, fn (array $row) => $row['kind'] === 'value')),
            'treeRows' => $treeRows,
            'answeredCount' => count(array_intersect_key($this->answers, array_flip(array_column($rows, 'key')))),
            'total' => count($rows),
            'editable' => $this->acceptsInput(),
            'firstName' => $this->user()->first_name ?? $this->user()->name,
            'individual' => $individual,
            'givenBy' => $this->givenBy(),
            'peerThreshold' => ThresholdPolicy::PEERS,
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::Experience, __('diagnosis.experience.question')))->title(DiagnosisStep::Experience->label());
    }

    /**
     * Who gave the values and the brand base the page asks about (MEGIS-3):
     * the author of the recorded version, named when it is someone else from
     * the organization. A peer is told at least that the leader gave them;
     * nothing is said when the user gave them or a mégis admin did.
     */
    private function givenBy(): ?string
    {
        $version = $this->main()->valueSetVersion;
        $set = $version === null ? app(OrganizationValueSetService::class)->forOrganization($this->organization) : null;
        $author = $version !== null ? $version->creator : ($set->latestVersion?->creator ?? $set->updater);

        if ($author?->is($this->user())) {
            return null;
        }

        if ($author !== null && ! $author->isAdmin()) {
            return __('diagnosis.experience.given_by', ['name' => $author->name]);
        }

        return $this->isLeader() ? null : __('diagnosis.experience.given_by_leader');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return app(ExperienceService::class)->rows($this->version(record: false)?->snapshot ?? $this->workingSnapshot());
    }

    /**
     * The recorded version once the diagnosis runs; before, the current value
     * set, recorded as a version when the answers are sent (08 · 8.4).
     */
    private function version(bool $record): ?Version
    {
        $main = $this->main();

        if ($main->value_set_version_id !== null) {
            return $main->valueSetVersion;
        }

        $set = app(OrganizationValueSetService::class)->forOrganization($this->organization);

        if ($record) {
            $set->recordVersion();
        }

        return $record ? $set->latestVersion()->first() : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function workingSnapshot(): array
    {
        return app(OrganizationValueSetService::class)->forOrganization($this->organization)->versionSnapshot();
    }
}
