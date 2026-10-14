<?php

namespace App\Services\Engine;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\DiagnosisStatus;
use App\Enums\ScoreResultState;
use App\Enums\ScoreState;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\Material;
use App\Models\MaterialValueScore;
use App\Models\Organization;
use App\Models\RawDetection;
use App\Models\User;
use App\Services\Diagnoses\DiagnosisService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The user diagnosis an engine result becomes (06 · 6.5): automatic,
 * "Jóváhagyásra vár", one row per scored value with its quotes; "nincs adat"
 * and the values marked "nem az enyém" stay out.
 */
class AutomaticDiagnosisBuilder
{
    public function __construct(
        private DiagnosisService $diagnoses,
        private ValueMeasurementEngine $engine,
        private DetectedValueRegistry $detectedValues,
    ) {}

    public function build(
        Material $material,
        User $user,
        Organization $organization,
        DiagnosisType $type,
        string $name,
        CarbonInterface $date,
        ?string $speaker = null,
    ): Diagnosis {
        return DB::transaction(function () use ($material, $user, $organization, $type, $name, $date, $speaker): Diagnosis {
            // Inside a main diagnosis it is a subdiagnosis with the parent's status (17 · 17.4).
            $parent = $material->parent_diagnosis_id === null ? null : Diagnosis::query()->find($material->parent_diagnosis_id);

            $diagnosis = $this->diagnoses->create([
                'name' => mb_substr($name, 0, 255),
                'organization_id' => $organization->id,
                'level' => DiagnosisLevel::User,
                'user_id' => $user->id,
                'diagnosis_type_id' => $type->id,
                'diagnosed_on' => $date->toDateString(),
                'material_id' => $material->id,
                'speaker' => $speaker,
                'parent_id' => $parent?->id,
            ], DiagnosisCreator::Automatic, $parent?->status ?? DiagnosisStatus::PendingApproval);

            $this->syncRows($diagnosis);

            // Values that matched nothing become "AI által detektált" (06 · 6.4).
            foreach ($this->unmatchedDetections($material, $speaker) as $detection) {
                $this->detectedValues->register($detection, $organization);
            }

            return $diagnosis;
        });
    }

    /**
     * Rebuilds the value rows from the scores, leaving out what the user marked
     * "nem az enyém"; used again when they change their mind on ET3.
     */
    public function syncRows(Diagnosis $diagnosis): void
    {
        $material = $diagnosis->material;

        if ($material === null || ! $diagnosis->status->isEditable()) {
            return;
        }

        $detections = $material->detections()->get()->keyBy('id');
        $scores = $material->scores()
            ->where('state', ScoreResultState::Scored)
            ->when($diagnosis->speaker !== null, fn ($query) => $query->where('speaker', $diagnosis->speaker))
            ->get();

        DB::transaction(function () use ($diagnosis, $scores, $detections): void {
            $diagnosis->valueRows()->delete();

            foreach ($scores->values() as $position => $score) {
                /** @var MaterialValueScore $score */
                $kept = collect($score->detection_ids)
                    ->map(fn (int $id) => $detections->get($id))
                    ->filter(fn (?RawDetection $detection) => $detection !== null && ! $detection->isExcluded());

                if ($kept->isEmpty()) {
                    continue;
                }

                $diagnosis->rows()->create([
                    'kind' => DiagnosisRowKind::Value,
                    'value_id' => $score->value_id,
                    'value_version_id' => $score->value_version_id,
                    'position' => $position,
                    'score' => $score->score,
                    'score_state' => ScoreState::Scored,
                    'reasoning' => $score->reasoning,
                    'quotes' => $kept->map(fn (RawDetection $detection) => [
                        'detection_id' => $detection->id,
                        'speaker' => $detection->speaker,
                        'quote' => $detection->quote,
                    ])->values()->all(),
                    'assigned_on' => $diagnosis->diagnosed_on,
                ]);
            }
        });
    }

    /**
     * Kept detections without a match above the threshold.
     *
     * @return Collection<int, RawDetection>
     */
    public function unmatchedDetections(Material $material, ?string $speaker): Collection
    {
        $matchedIds = $this->engine->matchedDetections($material, $speaker)->pluck('id')->all();

        return $material->detections()
            ->kept()
            ->when($speaker !== null, fn ($query) => $query->where('speaker', $speaker))
            ->whereNotIn('id', $matchedIds)
            ->orderBy('id')
            ->get();
    }
}
