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
use Illuminate\Support\Collection;

/**
 * The occasions behind a process diagnosis (13 · 13.5): date, materials, the
 * points of the occasion per value, the effectiveness and the quotes.
 */
class ProcessOccasionBreakdown
{
    /**
     * @return Collection<int, array{occasion: ProcessOccasion, materials: Collection<int, Material>, scores: array<int, ?float>, point: ?float, effectiveness: array<string, int>, quotes: list<string>}>
     */
    public function occasions(Diagnosis $diagnosis): Collection
    {
        $ids = collect($diagnosis->aggregation['used'] ?? [])->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        $occasions = ProcessOccasion::query()
            ->whereIn('id', $ids)
            ->with(['process', 'materials' => fn ($query) => $query->where('parent_diagnosis_id', $diagnosis->parent_id)->where('status', MaterialStatus::Processed)->with('user', 'creator')])
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
        $experiences = OccasionExperience::query()->whereIn('process_occasion_id', $ids)->whereNotNull('submitted_at')->get()->groupBy('process_occasion_id');
        $valueIds = $diagnosis->valueRows()->pluck('value_id');

        return $occasions->map(function (ProcessOccasion $occasion) use ($scores, $quotes, $experiences, $valueIds): array {
            $occasionScores = $occasion->materials->flatMap(fn (Material $material) => $scores->get($material->id, collect()));
            $perValue = $valueIds->mapWithKeys(fn (int $valueId) => [
                $valueId => ScoreMath::round1(ScoreMath::mean($occasionScores->where('value_id', $valueId)->pluck('score')->map(fn ($score) => (float) $score)->values()->all())),
            ])->all();

            return [
                'occasion' => $occasion,
                'materials' => $occasion->materials,
                'scores' => $perValue,
                'point' => ScoreMath::round1(ScoreMath::mean(array_values(array_filter($perValue, fn ($score) => $score !== null)))),
                'effectiveness' => collect(OccasionExperience::EFFECTIVENESS)->mapWithKeys(fn (string $answer) => [
                    $answer => $experiences->get($occasion->id, collect())->where('effectiveness', $answer)->count(),
                ])->all(),
                'quotes' => $occasionScores->flatMap(fn (MaterialValueScore $score) => $score->detection_ids ?? [])->unique()
                    ->map(fn (int $id) => $quotes->get($id))
                    ->filter()
                    ->values()
                    ->all(),
            ];
        });
    }

    /**
     * "2 új alkalom a publikálás óta" (13 · 13.4): occasions added after publishing.
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
            ->when($publishedAt !== null, fn ($query) => $query->where('created_at', '>', $publishedAt))
            ->whereHas('materials')
            ->count();
    }
}
