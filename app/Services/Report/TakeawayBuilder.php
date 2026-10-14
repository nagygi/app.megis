<?php

namespace App\Services\Report;

use App\Enums\ParticipantRole;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\OccasionExperience;
use App\Models\PersonalValueSet;
use App\Models\User;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\Diagnoses\ScoreMath;
use App\Services\MainDiagnoses\StepProgress;
use Illuminate\Support\Collection;

/**
 * T6 · Útravaló (29): the peer's own result instead of the report. Only
 * experience data and the own values, never a PHI and never another
 * person's name; the blocks are chosen automatically.
 */
class TakeawayBuilder
{
    /**
     * Fewer situations than this is not a pattern yet (29, question 2).
     */
    public const MIN_SITUATIONS = 2;

    /**
     * "A többiek" need at least this many people (29, question 1).
     */
    public const MIN_OTHERS = 3;

    public function __construct(
        private StepProgress $progress,
        private DiagnosisCalculator $calculator,
    ) {}

    /**
     * Who gets one: the peers who sent their T4 and did not withdraw (21, question 2).
     *
     * @return Collection<int, User>
     */
    public function recipients(MainDiagnosis $main): Collection
    {
        if (! $main->hasPeers()) {
            return collect();
        }

        $withdrawn = $this->calculator->withdrawnUserIds($main);

        return $main->peers()->with('user')->get()
            ->filter(fn (MainDiagnosisParticipant $peer) => ! in_array($peer->user_id, $withdrawn, true)
                && $this->progress->experienceDiagnosis($main, $peer->user) !== null)
            ->pluck('user')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function build(MainDiagnosis $main, User $peer): array
    {
        $values = collect($main->valueSetVersion?->snapshot['values'] ?? []);
        $start = $this->experienceOf($this->progress->experienceDiagnosis($main, $peer));
        $situations = OccasionExperience::query()
            ->where('main_diagnosis_id', $main->id)
            ->where('user_id', $peer->id)
            ->whereNotNull('submitted_at')
            ->get();
        $others = $this->others($main, $peer);

        $rows = $values->map(function (array $value) use ($start, $situations, $others): array {
            $answers = ScoreMath::numericAnswers($situations->map(fn (OccasionExperience $experience) => $experience->answers['value:'.$value['key']] ?? null));
            $situationMean = count($answers) >= self::MIN_SITUATIONS ? ScoreMath::round1(ScoreMath::mean($answers)) : null;
            $otherValues = $others->map(fn (Collection $rows) => $rows->get($value['value_id']))->filter(fn ($score) => $score !== null)->values()->all();

            return [
                'name' => $value['name'],
                'start' => $start->get($value['value_id']),
                'situations' => $situationMean,
                'situation_count' => count($answers),
                'others' => count($otherValues) >= self::MIN_OTHERS ? ScoreMath::round1(ScoreMath::mean($otherValues)) : null,
            ];
        })->values();

        $rows = $rows->map(fn (array $row) => [
            ...$row,
            // The only red on the page: two or more lower in the situations (29 · 29.1).
            'lower' => $row['start'] !== null && $row['situations'] !== null && $row['start'] - $row['situations'] >= 2,
        ]);

        return [
            'peer_id' => $peer->id,
            'subject_name' => $main->subjectName(),
            'rows' => $rows->all(),
            'strength' => $this->strength($rows),
            'own_value' => $this->ownValue($peer, $values),
            'self_gap' => $rows->filter(fn (array $row) => $row['start'] !== null && $row['situations'] !== null && abs($row['start'] - $row['situations']) >= 2)
                ->sortByDesc(fn (array $row) => abs($row['start'] - $row['situations']))
                ->first(),
            'others_gap' => $rows->filter(fn (array $row) => $row['start'] !== null && $row['others'] !== null)
                ->sortByDesc(fn (array $row) => abs($row['start'] - $row['others']))
                ->first(),
        ];
    }

    /**
     * The value rated highest in the situations, or at the start without them (29, question 5).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>|null
     */
    private function strength(Collection $rows): ?array
    {
        $withSituations = $rows->filter(fn (array $row) => $row['situations'] !== null);

        return $withSituations->isNotEmpty()
            ? $withSituations->sortByDesc('situations')->first()
            : $rows->filter(fn (array $row) => $row['start'] !== null)->sortByDesc('start')->first();
    }

    /**
     * One own value beyond the team's ones (24, 29 · 29.1).
     *
     * @param  Collection<int, array<string, mixed>>  $values
     * @return array<string, mixed>|null
     */
    private function ownValue(User $peer, Collection $values): ?array
    {
        $set = PersonalValueSet::query()->where('user_id', $peer->id)->first();
        $teamNames = $values->pluck('name')->map(fn ($name) => mb_strtolower(trim($name)));
        $teamIds = $values->pluck('value_id')->filter();

        $item = $set?->items()->orderBy('position')->get()
            ->first(fn ($item) => ! $teamIds->contains($item->value_id) && ! $teamNames->contains(mb_strtolower(trim($item->name))));

        return $item === null ? null : ['name' => $item->name, 'source' => $item->source, 'team_count' => $values->count()];
    }

    /**
     * value id => answer of a Megélés subdiagnosis.
     *
     * @return Collection<int|string, float>
     */
    private function experienceOf(?Diagnosis $diagnosis): Collection
    {
        if ($diagnosis === null) {
            return collect();
        }

        return $diagnosis->valueRows()->whereNotNull('experience')->get()->mapWithKeys(fn ($row) => [$row->value_id => (float) $row->experience]);
    }

    /**
     * The leader and the other peers, without the peer and without the withdrawn ones.
     *
     * @return Collection<int, Collection<int|string, float>>
     */
    private function others(MainDiagnosis $main, User $peer): Collection
    {
        $withdrawn = $this->calculator->withdrawnUserIds($main);

        return $main->participants()
            ->whereIn('role', [ParticipantRole::Leader, ParticipantRole::Peer])
            ->whereNull('excluded_at')
            ->whereNull('replaced_at')
            ->where('user_id', '!=', $peer->id)
            ->with('user')
            ->get()
            ->reject(fn (MainDiagnosisParticipant $participant) => in_array($participant->user_id, $withdrawn, true))
            ->map(fn (MainDiagnosisParticipant $participant) => $this->experienceOf($this->progress->experienceDiagnosis($main, $participant->user)))
            ->filter(fn (Collection $rows) => $rows->isNotEmpty())
            ->values();
    }
}
