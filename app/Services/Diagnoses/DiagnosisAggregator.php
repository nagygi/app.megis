<?php

namespace App\Services\Diagnoses;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\DiagnosisStatus;
use App\Enums\ExperienceState;
use App\Enums\MembershipStatus;
use App\Enums\ScoreState;
use App\Enums\TreeElement;
use App\Exceptions\DiagnosisException;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\Membership;
use App\Models\Value;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Aggregált adatok betöltése" (04 · 4.5): a team or organization diagnosis
 * from the latest published user diagnosis of each active member, at most
 * 12 months older than the diagnosis. Everyone counts once, with equal weight.
 */
class DiagnosisAggregator
{
    public const METHOD_VERSION = 1;

    public const WINDOW_MONTHS = 12;

    public const FEW_USERS = 3;

    public const DIVIDED_DEVIATION = 1.5;

    public const LOW_COVERAGE = 0.5;

    public const EXCLUDED_NO_DIAGNOSIS = 'no_diagnosis';

    public const EXCLUDED_TOO_OLD = 'too_old';

    public const EXCLUDED_INACTIVE = 'inactive';

    /**
     * What the load would write, for the preview table.
     *
     * @return array<string, mixed>
     */
    public function preview(Diagnosis $diagnosis): array
    {
        if (! $diagnosis->level->canAggregate()) {
            throw new DiagnosisException(__('admin.diagnoses.aggregation.not_available'));
        }

        $windowTo = $diagnosis->referenceDate()->copy()->startOfDay();
        $windowFrom = $windowTo->copy()->subMonthsNoOverflow(self::WINDOW_MONTHS);

        [$active, $excluded] = $this->population($diagnosis);
        $candidates = $this->userDiagnoses($diagnosis, $active->pluck('user_id')->all(), $windowTo);

        // Per user: where the score and where the experience comes from.
        $picks = [];

        foreach ($active as $membership) {
            $userDiagnoses = $candidates->get($membership->user_id, collect());
            $inWindow = $userDiagnoses->filter(fn (Diagnosis $source): bool => $source->referenceDate()->gte($windowFrom));

            // The score comes from the latest non-Megélés diagnosis, the experience
            // from the latest one with an experience (04 · 4.5, 08 · 8.3).
            $scoreSource = $inWindow->first(fn (Diagnosis $source): bool => ! $source->type->isExperience());
            $experienceSource = $inWindow->first(fn (Diagnosis $source): bool => $source->rows->contains(
                fn (DiagnosisRow $row): bool => in_array($row->experience_state, [ExperienceState::Answered, ExperienceState::DontKnow], true),
            ));

            if ($scoreSource === null && $experienceSource === null) {
                $excluded[] = $this->excludedEntry($membership, $userDiagnoses->isEmpty() ? self::EXCLUDED_NO_DIAGNOSIS : self::EXCLUDED_TOO_OLD);

                continue;
            }

            $picks[] = ['score' => $scoreSource, 'experience' => $experienceSource];
        }

        $activeCount = $active->count();
        $usableCount = count($picks);

        return [
            'method_version' => self::METHOD_VERSION,
            'level' => $diagnosis->level->value,
            'subject' => $diagnosis->subjectLabel(),
            'window' => ['from' => $windowFrom->toDateString(), 'to' => $windowTo->toDateString()],
            'active_count' => $activeCount,
            'usable_count' => $usableCount,
            'low_coverage' => $activeCount === 0 || $usableCount < $activeCount * self::LOW_COVERAGE,
            'sources' => $this->sourceList($picks),
            'excluded' => $excluded,
            'rows' => $this->rows($picks),
            'calculation' => __('admin.diagnoses.aggregation.calculation_text'),
            'thresholds' => __('admin.diagnoses.aggregation.thresholds_text', [
                'few' => self::FEW_USERS,
                'deviation' => number_format(self::DIVIDED_DEVIATION, 1, ',', ''),
                'coverage' => (int) (self::LOW_COVERAGE * 100),
            ]),
        ];
    }

    /**
     * Replaces every row with the aggregated data and saves how it was calculated.
     *
     * @return array<string, mixed> the saved description
     */
    public function load(Diagnosis $diagnosis): array
    {
        if (! $diagnosis->status->isEditable()) {
            throw new DiagnosisException(__('admin.diagnoses.published_readonly'));
        }

        $result = $this->preview($diagnosis);
        $loader = auth()->user();

        $description = [
            ...collect($result)->except('rows')->all(),
            'loaded_by' => $loader?->name,
            'loaded_at' => now()->toIso8601String(),
        ];

        DB::transaction(function () use ($diagnosis, $result, $description): void {
            $diagnosis->valueRows()->delete();
            $treeRows = $diagnosis->treeRows()->get()->keyBy(fn (DiagnosisRow $row) => $row->tree_element->value);
            $latestVersions = Value::query()
                ->whereIn('id', collect($result['rows'])->pluck('value_id')->filter())
                ->with('latestVersion')
                ->get()
                ->keyBy('id');

            foreach ($result['rows'] as $position => $row) {
                $attributes = [
                    'score' => $row['score']['mean'],
                    'score_state' => $row['score_state'],
                    'experience' => $row['experience']['mean'],
                    'experience_state' => $row['experience_state'],
                    'experience_met_example' => null,
                    'experience_unmet_example' => null,
                    'reasoning' => null,
                    'assigned_on' => $diagnosis->diagnosed_on,
                    // A brand base row nobody answered stays empty, not "n = 0".
                    'aggregation_stats' => $row['n'] === 0 ? null : [
                        'n' => $row['n'],
                        'score_deviation' => $row['score']['deviation'],
                        'experience_n' => $row['experience']['n'],
                        'experience_deviation' => $row['experience']['deviation'],
                        'warnings' => $row['warnings'],
                    ],
                    'manually_modified' => false,
                ];

                if ($row['kind'] === DiagnosisRowKind::TreeElement->value) {
                    $treeRows->get($row['tree_element'])?->update($attributes);

                    continue;
                }

                $diagnosis->rows()->create([
                    ...$attributes,
                    'kind' => DiagnosisRowKind::Value,
                    'value_id' => $row['value_id'],
                    // Values are combined across versions; the latest version is recorded (04 · 4.5).
                    'value_version_id' => $latestVersions->get($row['value_id'])?->latestVersion?->id,
                    'position' => $position,
                ]);
            }

            $diagnosis->update([
                'creator_kind' => DiagnosisCreator::Aggregated,
                'aggregation' => $description,
            ]);
        });

        return $description;
    }

    /**
     * Active members are the source; the others are listed as left out.
     *
     * @return array{0: Collection<int, Membership>, 1: list<array{user_name: string, reason: string, reason_label: string}>}
     */
    private function population(Diagnosis $diagnosis): array
    {
        $memberships = Membership::query()
            ->where('organization_id', $diagnosis->organization_id)
            ->when($diagnosis->level === DiagnosisLevel::Team, fn ($query) => $query->whereIn(
                'user_id',
                $diagnosis->team->users()->pluck('users.id'),
            ))
            ->with('user')
            ->get();

        [$active, $inactive] = $memberships->partition(
            fn (Membership $membership): bool => $membership->status === MembershipStatus::Active && $membership->user->isActive(),
        );

        return [
            $active->values(),
            $inactive->map(fn (Membership $membership) => $this->excludedEntry($membership, self::EXCLUDED_INACTIVE))->values()->all(),
        ];
    }

    /**
     * The published user diagnoses of each user up to the diagnosis date, newest first.
     *
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<int, Diagnosis>> keyed by user id
     */
    private function userDiagnoses(Diagnosis $diagnosis, array $userIds, mixed $windowTo): Collection
    {
        return Diagnosis::query()
            ->where('organization_id', $diagnosis->organization_id)
            ->where('level', DiagnosisLevel::User)
            ->where('status', DiagnosisStatus::Published)
            ->whereIn('user_id', $userIds)
            ->with(['user', 'rows', 'type'])
            ->get()
            ->filter(fn (Diagnosis $source): bool => $source->referenceDate()->lte($windowTo))
            ->sortBy([fn (Diagnosis $a, Diagnosis $b) => [$b->referenceDate(), $b->id] <=> [$a->referenceDate(), $a->id]])
            ->groupBy('user_id')
            ->map(fn (Collection $diagnoses) => $diagnoses->values());
    }

    /**
     * The diagnoses the calculation used, and for which metric.
     *
     * @param  list<array{score: ?Diagnosis, experience: ?Diagnosis}>  $picks
     * @return list<array{diagnosis_id: int, diagnosis_name: string, user_name: string, date: string, metric: string}>
     */
    private function sourceList(array $picks): array
    {
        $sources = [];

        foreach ($picks as $pick) {
            foreach (['score', 'experience'] as $metric) {
                $source = $pick[$metric];

                if ($source === null) {
                    continue;
                }

                if (isset($sources[$source->id])) {
                    $sources[$source->id]['metric'] = 'both';

                    continue;
                }

                $sources[$source->id] = [
                    'diagnosis_id' => $source->id,
                    'diagnosis_name' => $source->name,
                    'user_name' => $source->user->name,
                    'date' => $source->dateLabel(),
                    'metric' => $metric,
                ];
            }
        }

        return array_values($sources);
    }

    /**
     * Per value and per brand base element: mean, n, spread and warnings.
     * Each user counts once per row, with the score of their score source and
     * the experience of their experience source.
     *
     * @param  list<array{score: ?Diagnosis, experience: ?Diagnosis}>  $picks
     * @return list<array<string, mixed>>
     */
    private function rows(array $picks): array
    {
        $buckets = [];

        foreach (TreeElement::cases() as $element) {
            $buckets['tree:'.$element->value] = $this->emptyBucket(DiagnosisRowKind::TreeElement, null, $element->value, $element->label());
        }

        $keyOf = fn (DiagnosisRow $row): string => $row->kind === DiagnosisRowKind::Value ? 'value:'.$row->value_id : 'tree:'.$row->tree_element->value;

        foreach ($picks as $pick) {
            $contributedTo = [];

            foreach ($pick['score']?->rows ?? [] as $row) {
                $key = $keyOf($row);
                $buckets[$key] ??= $this->emptyBucket(DiagnosisRowKind::Value, $row->value_id, null, null);

                if ($row->score_state === ScoreState::Scored && $row->score !== null) {
                    $buckets[$key]['scores'][] = $row->score;
                    $contributedTo[$key] = true;
                } elseif ($row->score_state === ScoreState::NoTrace) {
                    $buckets[$key]['no_trace']++;
                    $contributedTo[$key] = true;
                }
            }

            foreach ($pick['experience']?->rows ?? [] as $row) {
                $key = $keyOf($row);
                $buckets[$key] ??= $this->emptyBucket(DiagnosisRowKind::Value, $row->value_id, null, null);

                if ($row->experience_state === ExperienceState::Answered && $row->experience !== null) {
                    $buckets[$key]['experiences'][] = $row->experience;
                    $contributedTo[$key] = true;
                } elseif ($row->experience_state === ExperienceState::DontKnow) {
                    $buckets[$key]['dont_know']++;
                    $contributedTo[$key] = true;
                }
            }

            foreach (array_keys($contributedTo) as $key) {
                $buckets[$key]['users']++;
            }
        }

        $valueNames = Value::query()->whereIn('id', collect($buckets)->pluck('value_id')->filter())->pluck('name', 'id');

        $rows = collect($buckets)
            ->filter(fn (array $bucket): bool => $bucket['kind'] === DiagnosisRowKind::TreeElement->value || $bucket['users'] > 0)
            ->map(function (array $bucket) use ($valueNames): array {
                $score = Statistics::summarize($bucket['scores']);
                $experience = Statistics::summarize($bucket['experiences']);

                $warnings = [];

                if ($bucket['users'] > 0 && $bucket['users'] < self::FEW_USERS) {
                    $warnings[] = 'few_users';
                }

                if (($score['deviation'] ?? 0) > self::DIVIDED_DEVIATION || ($experience['deviation'] ?? 0) > self::DIVIDED_DEVIATION) {
                    $warnings[] = 'divided';
                }

                return [
                    'kind' => $bucket['kind'],
                    'value_id' => $bucket['value_id'],
                    'tree_element' => $bucket['tree_element'],
                    'name' => $bucket['name'] ?? $valueNames->get($bucket['value_id']),
                    'n' => $bucket['users'],
                    'score' => $score,
                    'score_state' => match (true) {
                        $score['n'] > 0 => ScoreState::Scored->value,
                        $bucket['no_trace'] > 0 => ScoreState::NoTrace->value,
                        default => null,
                    },
                    'experience' => $experience,
                    'experience_state' => match (true) {
                        $experience['n'] > 0 => ExperienceState::Answered->value,
                        $bucket['dont_know'] > 0 => ExperienceState::DontKnow->value,
                        default => null,
                    },
                    'warnings' => $warnings,
                ];
            });

        // Values first by name, then the brand base in its fixed order.
        return $rows->where('kind', DiagnosisRowKind::Value->value)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->concat($rows->where('kind', DiagnosisRowKind::TreeElement->value))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyBucket(DiagnosisRowKind $kind, ?int $valueId, ?string $treeElement, ?string $name): array
    {
        return [
            'kind' => $kind->value,
            'value_id' => $valueId,
            'tree_element' => $treeElement,
            'name' => $name,
            'scores' => [],
            'experiences' => [],
            'no_trace' => 0,
            'dont_know' => 0,
            'users' => 0,
        ];
    }

    /**
     * @return array{user_name: string, reason: string, reason_label: string}
     */
    private function excludedEntry(Membership $membership, string $reason): array
    {
        return [
            'user_name' => $membership->user->name,
            'reason' => $reason,
            'reason_label' => __('admin.diagnoses.aggregation.excluded.'.$reason),
        ];
    }
}
