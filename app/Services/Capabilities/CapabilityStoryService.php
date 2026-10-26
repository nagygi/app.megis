<?php

namespace App\Services\Capabilities;

use App\Enums\MaterialContext;
use App\Enums\ParticipantRole;
use App\Models\CapabilityStory;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\PersonalValueSet;
use App\Models\User;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Képességek (NM-12): the leader and the peers tell situations to their
 * values, typed or spoken. Nobody scores: a sent situation becomes a material
 * the capability measurement reads, as self-report (KO-03, KO-06). The
 * suggested values come from the Megélés: the best and the worst lived
 * value of the organization, and for the leader of a Teljes one own value.
 */
class CapabilityStoryService
{
    public function __construct(
        private ExperienceService $experiences,
        private OrganizationValueSetService $organizationValues,
        private MaterialStore $materials,
        private MaterialPipeline $pipeline,
        private CapabilityAssessmentService $assessments,
    ) {}

    /**
     * The values a situation can be told to: the organization's first (the
     * measure of the diagnosis), then the own ones that are not among them.
     *
     * @return list<array{key: string, source: string, name: string, meaning: ?string, yours_too: bool}>
     */
    public function valuesFor(MainDiagnosis $main, User $user): array
    {
        $own = collect(PersonalValueSet::query()->where('user_id', $user->id)->first()?->versionSnapshot()['values'] ?? []);
        $ownIds = $own->pluck('value_id')->filter()->all();
        $ownNames = $own->map(fn (array $value) => mb_strtolower(trim($value['name'])))->all();
        $isOwn = fn (array $value) => ($value['value_id'] !== null && in_array($value['value_id'], $ownIds, true))
            || in_array(mb_strtolower(trim($value['name'])), $ownNames, true);

        $organization = collect($this->organizationSnapshot($main)['values'] ?? []);
        $values = $organization->map(fn (array $value) => [
            'key' => (string) $value['key'],
            'source' => CapabilityStory::SOURCE_ORGANIZATION,
            'name' => (string) $value['name'],
            'meaning' => $value['meaning'] ?? null,
            'yours_too' => $isOwn($value),
        ]);

        $organizationIds = $organization->pluck('value_id')->filter()->all();
        $organizationNames = $organization->map(fn (array $value) => mb_strtolower(trim($value['name'])))->all();

        $ownOnly = $own
            ->reject(fn (array $value) => ($value['value_id'] !== null && in_array($value['value_id'], $organizationIds, true))
                || in_array(mb_strtolower(trim($value['name'])), $organizationNames, true))
            ->map(fn (array $value) => [
                'key' => (string) $value['key'],
                'source' => CapabilityStory::SOURCE_OWN,
                'name' => (string) $value['name'],
                'meaning' => $value['meaning'] ?? null,
                'yours_too' => false,
            ]);

        return $values->concat($ownOnly)->values()->all();
    }

    /**
     * The user's situations; on the first visit the suggested ones are made.
     *
     * @return Collection<int, CapabilityStory>
     */
    public function storiesFor(MainDiagnosis $main, User $user, ParticipantRole $role): Collection
    {
        $stories = $this->query($main, $user)->get();

        if ($stories->isNotEmpty() || ! $main->acceptsInput()) {
            return $stories;
        }

        foreach ($this->suggestions($main, $user, $role) as $position => $suggestion) {
            $this->create($main, $user, $suggestion['value'], $suggestion['kind'], $position);
        }

        return $this->query($main, $user)->get();
    }

    /**
     * How many situations the step suggests (17 · 17.3, NM-12): fewer in a snapshot and for a peer.
     */
    public function suggestedCount(MainDiagnosis $main, ParticipantRole $role): int
    {
        return (int) config('diagnosis.capability_stories.'.($role === ParticipantRole::Peer ? 'peer' : $main->product_key), 1);
    }

    public function save(CapabilityStory $story, string $answer): void
    {
        $story->update(['answer' => trim($answer) === '' ? null : $answer]);
    }

    /**
     * Another value on the card; the question stays.
     */
    public function changeValue(CapabilityStory $story, string $key): void
    {
        $value = collect($this->valuesFor($story->mainDiagnosis, $story->user))->firstWhere('key', $key);

        if ($value === null) {
            return;
        }

        $story->update([
            'value_source' => $value['source'],
            'value_key' => $value['key'],
            'value_name' => $value['name'],
            'value_meaning' => $value['meaning'],
        ]);
    }

    /**
     * "+ Még egy helyzet": the first value without a card yet.
     */
    public function add(MainDiagnosis $main, User $user): ?CapabilityStory
    {
        $stories = $this->query($main, $user)->get();

        if ($stories->count() >= (int) config('diagnosis.capability_stories.max')) {
            return null;
        }

        $values = collect($this->valuesFor($main, $user));
        $value = $values->first(fn (array $value) => ! $stories->contains('value_key', $value['key'])) ?? $values->first();

        if ($value === null) {
            return null;
        }

        $kind = $value['source'] === CapabilityStory::SOURCE_OWN ? CapabilityStory::KIND_OWN : CapabilityStory::KIND_TESTED;

        return $this->create($main, $user, $value, $kind, (int) $stories->max('position') + 1);
    }

    /**
     * ×: the card goes, and with it its material, traces and findings (cascade).
     */
    public function remove(CapabilityStory $story): void
    {
        $main = $story->mainDiagnosis;
        $hadMaterial = $story->material_id !== null;

        DB::transaction(function () use ($story): void {
            $story->material?->delete();
            $story->delete();
        });

        if ($hadMaterial) {
            $this->assessments->refresh($main->diagnosis);
        }
    }

    /**
     * "Tovább": every answered card becomes a material the capability
     * measurement reads; a changed answer is measured again, an emptied one
     * is taken back. Returns the number of sent situations.
     */
    public function submit(MainDiagnosis $main, User $user, ParticipantRole $role): int
    {
        $refresh = false;

        foreach ($this->query($main, $user)->with('material')->get() as $story) {
            if (! $story->hasAnswer()) {
                if ($story->material !== null) {
                    $story->material->delete();
                    $story->update(['material_id' => null, 'submitted_at' => null]);
                    $refresh = true;
                }

                continue;
            }

            $turns = $this->turns($story, $role);

            if ($story->material === null) {
                $material = $this->materials->fromTurns($turns, MaterialContext::CapabilityStory, $user, $main->diagnosis->organization);
                $material->update(['parent_diagnosis_id' => $main->diagnosis_id]);
                $story->update(['material_id' => $material->id, 'submitted_at' => now()]);
                $this->pipeline->start($material);

                continue;
            }

            $attributes = MaterialStore::turnAttributes($turns);

            if ($story->material->text !== $attributes['text']) {
                $story->material->update($attributes);
                $story->update(['submitted_at' => now()]);
                $this->pipeline->measureCapabilities($story->material, force: true);
            }
        }

        if ($refresh) {
            $this->assessments->refresh($main->diagnosis);
        }

        return $this->query($main, $user)->whereNotNull('submitted_at')->count();
    }

    /**
     * The material of a situation: the question as the mégis', the answer as the user's.
     *
     * @return list<array{speaker: string, text: string}>
     */
    public function turns(CapabilityStory $story, ParticipantRole $role): array
    {
        $value = collect([$story->value_name, $story->value_meaning])->filter()->implode(' · ');

        return [
            ['speaker' => __('capabilities.step.speaker_question'), 'text' => $value.'. '.$story->question],
            ['speaker' => __('capabilities.step.speaker_'.$role->value), 'text' => (string) $story->answer],
        ];
    }

    /**
     * Situations sent in the diagnosis: all, and those of the peers (V10).
     *
     * @return array{total: int, peers: int}
     */
    public function counts(MainDiagnosis $main): array
    {
        $sent = $main->capabilityStories()->whereNotNull('submitted_at')->get(['user_id']);

        return [
            'total' => $sent->count(),
            'peers' => $sent->where('user_id', '!=', $main->leader_id)->count(),
        ];
    }

    /**
     * The suggested cards (NM-12): the organization's values ordered by the
     * user's Megélés; the best lived one is tested, the worst one missed.
     *
     * @return list<array{value: array{key: string, source: string, name: string, meaning: ?string, yours_too: bool}, kind: string}>
     */
    private function suggestions(MainDiagnosis $main, User $user, ParticipantRole $role): array
    {
        $values = collect($this->valuesFor($main, $user));
        $organization = $values->where('source', CapabilityStory::SOURCE_ORGANIZATION)->values();
        $own = $values->where('source', CapabilityStory::SOURCE_OWN)->values();

        if ($organization->isEmpty()) {
            return $own->take(1)->map(fn (array $value) => ['value' => $value, 'kind' => CapabilityStory::KIND_OWN])->all();
        }

        $answers = $this->experienceAnswers($main, $user, $role);
        // Unanswered and "nem tudom" in the middle, so a lived value comes first at both ends.
        $score = fn (array $value) => is_numeric($answers['value:'.$value['key']] ?? null) ? (int) $answers['value:'.$value['key']] : 3.5;
        $ranked = $organization->sortBy($score)->values();
        $count = $this->suggestedCount($main, $role);

        $missed = $ranked->first();
        $cards = [['value' => $missed, 'kind' => CapabilityStory::KIND_MISSED]];

        if ($count >= 2 && $ranked->count() >= 2) {
            array_unshift($cards, ['value' => $ranked->last(), 'kind' => CapabilityStory::KIND_TESTED]);
        }

        if ($count >= 3) {
            $third = $own->first();
            $cards[] = $third !== null
                ? ['value' => $third, 'kind' => CapabilityStory::KIND_OWN]
                : ($ranked->count() >= 3 ? ['value' => $ranked->get(1), 'kind' => CapabilityStory::KIND_MISSED] : null);
        }

        return array_values(array_filter($cards));
    }

    /**
     * @return array<string, string>
     */
    private function experienceAnswers(MainDiagnosis $main, User $user, ParticipantRole $role): array
    {
        $participant = MainDiagnosisParticipant::query()
            ->where('main_diagnosis_id', $main->id)
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->first();

        return $this->experiences->answersFor($main, $user, $participant?->experience_draft);
    }

    /**
     * The recorded value set once the diagnosis runs; before, the working one (08 · 8.4).
     *
     * @return array<string, mixed>
     */
    private function organizationSnapshot(MainDiagnosis $main): array
    {
        return $main->valueSetVersion?->snapshot
            ?? $this->organizationValues->forOrganization($main->diagnosis->organization)->versionSnapshot();
    }

    /**
     * @param  array{key: string, source: string, name: string, meaning: ?string, yours_too: bool}  $value
     */
    private function create(MainDiagnosis $main, User $user, array $value, string $kind, int $position): CapabilityStory
    {
        return $main->capabilityStories()->create([
            'user_id' => $user->id,
            'value_source' => $value['source'],
            'value_key' => $value['key'],
            'value_name' => $value['name'],
            'value_meaning' => $value['meaning'],
            'kind' => $kind,
            'question' => __('capabilities.step.questions.'.$kind),
            'position' => $position,
        ]);
    }

    /**
     * @return HasMany<CapabilityStory, MainDiagnosis>
     */
    private function query(MainDiagnosis $main, User $user): HasMany
    {
        return $main->capabilityStories()->where('user_id', $user->id)->orderBy('position')->orderBy('id');
    }
}
