<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Models\Version;
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
    use InteractsWithMainDiagnosis;

    /**
     * @var array<string, string>
     */
    public array $answers = [];

    public ?string $openKey = null;

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
        $this->dispatch('saved');
    }

    public function toggle(string $key): void
    {
        $this->openKey = $this->openKey === $key ? null : $key;
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

        return view('livewire.client.diagnosis.experience', [
            'valueRows' => array_values(array_filter($rows, fn (array $row) => $row['kind'] === 'value')),
            'treeRows' => array_values(array_filter($rows, fn (array $row) => $row['kind'] === 'tree_element')),
            'answeredCount' => count(array_intersect_key($this->answers, array_flip(array_column($rows, 'key')))),
            'total' => count($rows),
            'editable' => $this->acceptsInput(),
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::Experience))->title(DiagnosisStep::Experience->label());
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
