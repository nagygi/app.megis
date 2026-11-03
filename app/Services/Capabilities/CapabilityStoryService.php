<?php

namespace App\Services\Capabilities;

use App\Enums\MaterialContext;
use App\Enums\ParticipantRole;
use App\Models\CapabilityStory;
use App\Models\MainDiagnosis;
use App\Models\User;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Képességek (NM-12): the leader and the peers tell recent situations in one
 * text, typed, spoken or uploaded. Nobody scores and no capability is named:
 * a sent text becomes a material the capability measurement reads against
 * the Képességtár, as self-report (KO-03, KO-06).
 */
class CapabilityStoryService
{
    public function __construct(
        private MaterialStore $materials,
        private MaterialPipeline $pipeline,
        private CapabilityAssessmentService $assessments,
    ) {}

    public function storyFor(MainDiagnosis $main, User $user): ?CapabilityStory
    {
        return $this->query($main, $user)->first();
    }

    /**
     * The text is kept as typed; the question is the one asked when it was started.
     */
    public function save(MainDiagnosis $main, User $user, string $answer): CapabilityStory
    {
        $story = $main->capabilityStories()->firstOrNew(['user_id' => $user->id], ['question' => __('capabilities.step.prompt')]);
        $story->answer = trim($answer) === '' ? null : $answer;
        $story->save();

        return $story;
    }

    /**
     * "Tovább": a told text becomes a material the capability measurement
     * reads; a changed one is measured again, an emptied one is taken back.
     * Returns whether a text is sent.
     */
    public function submit(MainDiagnosis $main, User $user, ParticipantRole $role): bool
    {
        $story = $this->query($main, $user)->with('material')->first();

        if ($story === null) {
            return false;
        }

        if (! $story->hasAnswer()) {
            if ($story->material !== null) {
                $story->material->delete();
                $story->update(['material_id' => null, 'submitted_at' => null]);
                $this->assessments->refresh($main->diagnosis);
            }

            return false;
        }

        $turns = $this->turns($story, $role);

        if ($story->material === null) {
            $material = $this->materials->fromTurns($turns, MaterialContext::CapabilityStory, $user, $main->diagnosis->organization);
            $material->update(['parent_diagnosis_id' => $main->diagnosis_id]);
            $story->update(['material_id' => $material->id, 'submitted_at' => now()]);
            $this->pipeline->start($material);

            return true;
        }

        $attributes = MaterialStore::turnAttributes($turns);

        if ($story->material->text !== $attributes['text']) {
            $story->material->update($attributes);
            $story->update(['submitted_at' => now()]);
            $this->pipeline->measureCapabilities($story->material, force: true);
        }

        return true;
    }

    /**
     * The material of a text: the question as the mégis', the answer as the user's.
     *
     * @return list<array{speaker: string, text: string}>
     */
    public function turns(CapabilityStory $story, ParticipantRole $role): array
    {
        return [
            ['speaker' => __('capabilities.step.speaker_question'), 'text' => $story->question],
            ['speaker' => __('capabilities.step.speaker_'.$role->value), 'text' => (string) $story->answer],
        ];
    }

    /**
     * Texts sent in the diagnosis: all, and those of the peers (V10).
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
     * @return HasMany<CapabilityStory, MainDiagnosis>
     */
    private function query(MainDiagnosis $main, User $user): HasMany
    {
        return $main->capabilityStories()->where('user_id', $user->id);
    }
}
