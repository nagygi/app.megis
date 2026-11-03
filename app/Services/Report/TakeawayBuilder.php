<?php

namespace App\Services\Report;

use App\Enums\MembershipStatus;
use App\Enums\ParticipantRole;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\Membership;
use App\Models\OccasionExperience;
use App\Models\PersonalValueSet;
use App\Models\User;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\Diagnoses\ScoreMath;
use App\Services\MainDiagnoses\StepProgress;
use App\Services\Values\PersonalValueSetService;
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
        private PersonalValueSetService $personalValues,
    ) {}

    /**
     * Who gets one: the peers who sent their T4 and did not withdraw (21, question 2).
     * An inactive member could not open it (02, 29 · 29.2): no Útravaló, no e-mail.
     *
     * @return Collection<int, User>
     */
    public function recipients(MainDiagnosis $main): Collection
    {
        if (! $main->hasPeers()) {
            return collect();
        }

        $withdrawn = $this->calculator->withdrawnUserIds($main);
        $inactive = Membership::query()
            ->where('organization_id', $main->diagnosis->organization_id)
            ->where('status', MembershipStatus::Inactive)
            ->pluck('user_id')
            ->all();

        return $main->peers()->with('user')->get()
            ->filter(fn (MainDiagnosisParticipant $peer) => ! in_array($peer->user_id, $withdrawn, true)
                && ! in_array($peer->user_id, $inactive, true)
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
            ->with('occasion.process')
            ->get();
        $others = $this->others($main, $peer);

        $rows = $values->map(function (array $value) use ($start, $situations, $others): array {
            $answers = ScoreMath::numericAnswers($situations->map(fn (OccasionExperience $experience) => $experience->answers['value:'.$value['key']] ?? null));
            $situationMean = count($answers) >= self::MIN_SITUATIONS ? ScoreMath::round1(ScoreMath::mean($answers)) : null;
            $otherValues = $others->map(fn (Collection $rows) => $rows->get($value['value_id']))->filter(fn ($score) => $score !== null)->values()->all();

            return [
                'key' => $value['key'],
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

        $selfGap = $rows->filter(fn (array $row) => $row['start'] !== null && $row['situations'] !== null && abs($row['start'] - $row['situations']) >= 2)
            ->sortByDesc(fn (array $row) => abs($row['start'] - $row['situations']))
            ->first();

        return [
            'peer_id' => $peer->id,
            'subject_name' => $main->subjectName(),
            'rows' => $rows->all(),
            // "3 helyzetből 3": all the peer's situations, and those that rated the value (29 · 29.1).
            'situation_total' => $situations->count(),
            'strength' => $this->strength($rows),
            'own_value' => $this->ownValue($peer, $values, $main->participantFor($peer, ParticipantRole::Peer)?->frozenOwnValues()),
            'self_gap' => $selfGap === null ? null : [...$selfGap, 'process' => $this->mainProcess($selfGap, $situations)],
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
     * One own value beyond the team's ones (24, 29 · 29.1): from the list
     * frozen at publishing, the live one before it (21 · 21.3).
     *
     * @param  Collection<int, array<string, mixed>>  $values
     * @param  list<array<string, mixed>>|null  $frozen
     * @return array<string, mixed>|null
     */
    private function ownValue(User $peer, Collection $values, ?array $frozen): ?array
    {
        $own = $frozen !== null
            ? collect($frozen)
            : (PersonalValueSet::query()->where('user_id', $peer->id)->first()?->items()->get()->map(fn ($item) => $item->only(['value_id', 'name', 'source'])) ?? collect());
        $teamNames = $values->pluck('name')->map(fn ($name) => mb_strtolower(trim($name)));
        $teamIds = $values->pluck('value_id')->filter();

        $item = $own->first(fn (array $item) => ! $teamIds->contains($item['value_id'] ?? null) && ! $teamNames->contains(mb_strtolower(trim($item['name']))));

        if ($item === null) {
            return null;
        }

        // "· Értéktükör · {dátum} · 2 helyzetben": where it came from and how often it shows (24 · 24.4).
        $occurrences = isset($item['value_id']) ? $this->personalValues->occurrences($peer, [(int) $item['value_id']]) : ['counts' => []];
        $source = $item['source'] ?? null;

        return [
            'name' => $item['name'],
            'source' => $source,
            'team_count' => $values->count(),
            'mirror_on' => $source === 'mirror' ? $this->personalValues->latestMirror($peer)?->created_at?->toDateString() : null,
            'situations' => $occurrences['counts'][(int) ($item['value_id'] ?? 0)] ?? 0,
        ];
    }

    /**
     * "★ fő eltérés · Ops heti": the process whose situations stand furthest
     * from the start, by the mean of the peer's answers there (29 · 29.1).
     *
     * @param  array<string, mixed>  $row
     * @param  Collection<int, OccasionExperience>  $situations
     */
    private function mainProcess(array $row, Collection $situations): ?string
    {
        return $situations
            ->filter(fn (OccasionExperience $experience) => $experience->occasion?->process !== null)
            ->groupBy(fn (OccasionExperience $experience) => $experience->occasion->process_id)
            ->map(fn (Collection $group) => [
                'name' => $group->first()->occasion->process->name,
                'mean' => ScoreMath::mean(ScoreMath::numericAnswers($group->map(fn (OccasionExperience $experience) => $experience->answers['value:'.$row['key']] ?? null))),
            ])
            ->filter(fn (array $process) => $process['mean'] !== null)
            ->sortByDesc(fn (array $process) => abs($row['start'] - $process['mean']))
            ->first()['name'] ?? null;
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
