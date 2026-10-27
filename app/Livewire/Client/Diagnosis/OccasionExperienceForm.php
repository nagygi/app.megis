<?php

namespace App\Livewire\Client\Diagnosis;

use App\Livewire\Client\Diagnosis\Concerns\HandlesOccasionMaterials;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Livewire\Concerns\WithDictation;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\OccasionExperience;
use App\Models\Organization;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\Processes\OccasionService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * V11·c · A megélésed (12 · 12.4): after placing a material, before the
 * analysis. Each row 1–6 or "nem tudom", whether it was effective, a note;
 * "Kihagyom" is an equal way and can be made up later. Afterwards back home
 * (V10, T5), or to T5·b when opened from there.
 */
class OccasionExperienceForm extends Component
{
    use HandlesOccasionMaterials, InteractsWithMainDiagnosis, WithDictation, WithFileUploads;

    public string $materialUuid = '';

    /**
     * @var array<string, string>
     */
    public array $answers = [];

    public ?string $effectiveness = null;

    public string $note = '';

    /**
     * "helyzeteim" when opened from T5·b: the way back leads there.
     */
    public string $from = '';

    public function mount(Organization $organization, Diagnosis $diagnosis, Material $material): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        $material = $this->ownMaterial($material->uuid);
        abort_if($material->process_occasion_id === null, 404);

        $this->materialUuid = $material->uuid;
        $this->from = request()->query('from') === 'helyzeteim' ? 'helyzeteim' : '';
        $existing = OccasionExperience::query()->where('process_occasion_id', $material->process_occasion_id)->where('user_id', $this->user()->id)->first();

        if ($existing?->isSubmitted()) {
            $this->answers = $existing->answers ?? [];
            $this->effectiveness = $existing->effectiveness;
            $this->note = (string) $existing->note;
        }
    }

    public function submit(OccasionService $occasions): void
    {
        $this->authorizeExperience();
        $this->resetErrorBag();

        $keys = array_column($this->rows(), 'key');
        $missing = array_diff($keys, array_keys(array_filter($this->answers)));

        if ($missing !== []) {
            throw ValidationException::withMessages(['answers' => __('diagnosis.experience.missing', ['count' => count($missing)])]);
        }

        $this->validate([
            'answers.*' => [Rule::in(['1', '2', '3', '4', '5', '6', ExperienceService::DONT_KNOW])],
            'effectiveness' => ['required', Rule::in(OccasionExperience::EFFECTIVENESS)],
            'note' => ['nullable', 'string', 'max:3000'],
        ], attributes: ['effectiveness' => __('processes.occasions.effective'), 'note' => __('processes.occasions.note')]);

        $occasions->experience($this->material(), $this->user(), array_intersect_key($this->answers, array_flip($keys)), $this->effectiveness, $this->note, skip: false);

        session()->flash('status', __('processes.occasions.experience_saved'));
        $this->redirect($this->returnUrl(), navigate: false);
    }

    public function skip(OccasionService $occasions): void
    {
        $this->authorizeExperience();

        $occasions->experience($this->material(), $this->user(), null, null, null, skip: true);
        $this->redirect($this->returnUrl(), navigate: false);
    }

    public function render(): View
    {
        $rows = $this->rows();
        $material = $this->material()->load('occasion.process');

        return view('livewire.client.diagnosis.occasion-experience', [
            'material' => $material,
            'valueRows' => array_values(array_filter($rows, fn (array $row) => $row['kind'] === 'value')),
            'treeRows' => array_values(array_filter($rows, fn (array $row) => $row['kind'] === 'tree_element')),
            'answeredCount' => count(array_intersect_key(array_filter($this->answers), array_flip(array_column($rows, 'key')))),
            'total' => count($rows),
            'editable' => $this->acceptsInput(),
        ])->layout('layouts::client', [
            'title' => __('processes.occasions.experience_title'),
            // As in the prototype V11·c: the question, then "Retró · 03.14. · jegyzet feltöltve".
            'symbol' => 'fruit',
            'subtitle' => __('processes.occasions.experience_question'),
            'note' => collect([$material->occasion?->process?->name, $material->occasion?->occurred_at?->format('m.d.'), $material->is_situation ? __('processes.occasions.situation') : __('processes.occasions.uploaded')])->filter()->implode(' · '),
            'back' => $this->fromSituations() ? $this->situationsUrl() : $this->contributeUrl(),
            'backLabel' => $this->fromSituations() ? __('processes.occasions.situations_title') : __('processes.occasions.contribute_title'),
            'color' => 'cream',
        ])->title(__('processes.occasions.experience_title'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return app(ExperienceService::class)->rows($this->main()->valueSetVersion?->snapshot ?? []);
    }

    private function fromSituations(): bool
    {
        return $this->from === 'helyzeteim';
    }

    private function situationsUrl(): string
    {
        return route('client.diagnosis.situations', [$this->organization, $this->diagnosis]);
    }

    /**
     * After "Beküldöm" or "Kihagyom": T5·b when opened from there, otherwise the home (prototype V11·c).
     */
    private function returnUrl(): string
    {
        return $this->fromSituations() ? $this->situationsUrl() : $this->overviewUrl();
    }

    private function material(): Material
    {
        return $this->ownMaterial($this->materialUuid);
    }

    /**
     * What goes into the calculation closes when reading starts (15, question 2).
     */
    private function authorizeExperience(): void
    {
        abort_unless($this->acceptsInput() && $this->ensurePeerStarted(), 403, __('diagnosis.locked'));
    }

    protected function canDictate(string $field): bool
    {
        return $field === 'note' && $this->acceptsInput() && ! $this->isImpersonating();
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->organization;
    }

    protected function dictationLimit(string $field): ?int
    {
        return 3000;
    }
}
