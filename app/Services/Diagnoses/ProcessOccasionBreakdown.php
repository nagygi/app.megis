<?php

namespace App\Services\Diagnoses;

use App\Enums\DiagnosisStatus;
use App\Enums\MaterialStatus;
use App\Enums\ScoreResultState;
use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\MaterialValueScore;
use App\Models\OccasionExperience;
use App\Models\ProcessOccasion;
use App\Models\RawDetection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * The occasions behind a process diagnosis (13 · 13.5): date, materials, the
 * points of the occasion per value, the effectiveness and the quotes. The
 * same materials and answers as the calculation, so the numbers match.
 */
class ProcessOccasionBreakdown
{
    public function __construct(private DiagnosisCalculator $calculator) {}

    /**
     * @return Collection<int, array{occasion: ProcessOccasion, materials: Collection<int, Material>, scores: array<int, ?float>, point: ?float, effectiveness: ?string, answers: array<string, int>, quotes: array<int, list<string>>}>
     */
    public function occasions(Diagnosis $diagnosis): Collection
    {
        $ids = collect($diagnosis->aggregation['used'] ?? [])->pluck('id');
        $main = $diagnosis->parent?->main;

        if ($ids->isEmpty() || $main === null) {
            return collect();
        }

        $withdrawn = $this->calculator->withdrawnUserIds($main);
        $occasions = ProcessOccasion::query()
            ->whereIn('id', $ids)
            ->with(['process', 'materials' => fn (Relation $query) => $this->used($query->getQuery(), $diagnosis, $withdrawn)->with('user', 'creator')])
            ->orderBy('occurred_at')
            ->get();
        $materialIds = $occasions->flatMap(fn (ProcessOccasion $occasion) => $occasion->materials->pluck('id'));
        $scores = MaterialValueScore::query()
            ->whereIn('material_id', $materialIds)
            ->whereNull('speaker')
            ->where('state', ScoreResultState::Scored)
            ->get()
            ->groupBy('material_id');
        $quotes = RawDetection::query()
            ->whereIn('id', $scores->flatten()->flatMap(fn (MaterialValueScore $score) => $score->detection_ids ?? []))
            ->whereNull('excluded_at')
            ->pluck('quote', 'id');
        $experiences = OccasionExperience::query()
            ->where('main_diagnosis_id', $main->id)
            ->whereIn('process_occasion_id', $ids)
            ->whereNotNull('submitted_at')
            ->whereNotIn('user_id', $withdrawn)
            ->get()
            ->groupBy('process_occasion_id');
        $valueIds = $diagnosis->valueRows()->pluck('value_id');

        return $occasions->map(function (ProcessOccasion $occasion) use ($scores, $quotes, $experiences, $valueIds): array {
            // A value the AI scored twice in one material counts once, as in the calculation.
            $byValue = $valueIds->mapWithKeys(fn (int $valueId) => [
                $valueId => $occasion->materials->map(fn (Material $material) => $scores->get($material->id, collect())->firstWhere('value_id', $valueId))->filter()->values(),
            ]);
            $perValue = $byValue->map(fn (Collection $valueScores) => ScoreMath::round1(ScoreMath::mean($valueScores->map(fn (MaterialValueScore $score) => (float) $score->score)->all())))->all();
            $answers = $experiences->get($occasion->id, collect())->pluck('effectiveness');

            return [
                'occasion' => $occasion,
                'materials' => $occasion->materials,
                'scores' => $perValue,
                'point' => ScoreMath::round1(ScoreMath::mean(array_values(array_filter($perValue, fn ($score) => $score !== null)))),
                'effectiveness' => ScoreMath::effectivenessVerdict($answers->all()),
                'answers' => collect(OccasionExperience::EFFECTIVENESS)->mapWithKeys(fn (string $answer) => [
                    $answer => $answers->filter(fn ($given) => $given === $answer)->count(),
                ])->all(),
                'quotes' => $byValue
                    ->map(fn (Collection $valueScores) => $valueScores->flatMap(fn (MaterialValueScore $score) => $score->detection_ids ?? [])->unique()
                        ->map(fn (int $id) => $quotes->get($id))
                        ->filter()
                        ->values()
                        ->all())
                    ->filter()
                    ->all(),
            ];
        });
    }

    /**
     * The materials of the occasions a calculated process diagnosis used.
     *
     * @return list<int>
     */
    public function materialIds(Diagnosis $diagnosis): array
    {
        $main = $diagnosis->parent?->main;

        if ($main === null) {
            return [];
        }

        return $this->used(Material::query(), $diagnosis, $this->calculator->withdrawnUserIds($main))
            ->whereIn('process_occasion_id', collect($diagnosis->aggregation['used'] ?? [])->pluck('id'))
            ->pluck('id')
            ->all();
    }

    /**
     * "2 új alkalom a publikálás óta" (13 · 13.4): occasions the diagnosis did
     * not use whose processed material came after publishing, also calendar
     * occasions recorded ahead.
     */
    public function newSincePublishing(Diagnosis $diagnosis): int
    {
        if ($diagnosis->status !== DiagnosisStatus::Published || $diagnosis->process === null) {
            return 0;
        }

        $publishedAt = ($diagnosis->parent ?? $diagnosis)->transitions()->where('to_status', DiagnosisStatus::Published)->latest('id')->value('created_at');
        $processIds = [$diagnosis->process_id, ...$diagnosis->process->children()->pluck('id')->all()];

        return ProcessOccasion::query()
            ->whereIn('process_id', $processIds)
            ->whereNotIn('id', collect($diagnosis->aggregation['used'] ?? [])->pluck('id'))
            ->whereHas('materials', fn (Builder $query) => $query
                ->where('parent_diagnosis_id', $diagnosis->parent_id)
                ->where('status', MaterialStatus::Processed)
                ->when($publishedAt !== null, fn (Builder $query) => $query->where('created_at', '>', $publishedAt)))
            ->count();
    }

    /**
     * Processed materials of the main diagnosis, without withdrawn peers (25 · 25.6).
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @param  list<int>  $withdrawn
     * @return TBuilder
     */
    private function used(Builder $query, Diagnosis $diagnosis, array $withdrawn): Builder
    {
        return $query
            ->where('parent_diagnosis_id', $diagnosis->parent_id)
            ->where('status', MaterialStatus::Processed)
            ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhereNotIn('user_id', $withdrawn));
    }
}
