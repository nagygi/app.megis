<?php

namespace App\Services\Diagnoses;

use App\Enums\DiagnosisRowKind;
use App\Enums\DiagnosisStatus;
use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Enums\MaterialStatus;
use App\Enums\ScoreResultState;
use App\Enums\ScoreState;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\MaterialValueScore;
use App\Models\Organization;
use App\Models\User;
use App\Services\MainDiagnoses\StepProgress;
use Illuminate\Support\Collection;

/**
 * The data of the report's view switch (18 · 18.5): Csapat (the rows of the
 * diagnosis), Egyén (the viewer's own materials and own experience) and
 * Szervezet (the latest published diagnosis per team, the common rows). The
 * team experience and the gap follow the threshold (18 · 18.6).
 *
 * A row: kind, key, label, score, score_state, experience, gap.
 */
class ViewDataBuilder
{
    /**
     * Fewer own materials than this: "nincs elég adat" (18 · 18.5).
     */
    public const INDIVIDUAL_MINIMUM = 2;

    public function __construct(
        private ThresholdPolicy $threshold,
        private StepProgress $progress,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function team(MainDiagnosis $main): array
    {
        $showExperience = $this->threshold->showsTeamExperience($main);

        return $this->rowsOf($main->diagnosis)
            ->map(fn (DiagnosisRow $row) => $this->row($row, $row->score, $row->score_state, $showExperience ? $row->experience : null))
            ->values()
            ->all();
    }

    /**
     * "a te anyagaidból": the PHI from the viewer's own materials, the experience their own V5 or T4.
     *
     * @return list<array<string, mixed>>
     */
    public function individual(MainDiagnosis $main, User $viewer): array
    {
        $materials = Material::query()
            ->where('parent_diagnosis_id', $main->diagnosis_id)
            ->where('user_id', $viewer->id)
            ->where('status', MaterialStatus::Processed)
            ->pluck('id');
        $scores = MaterialValueScore::query()
            ->whereIn('material_id', $materials)
            ->whereNull('speaker')
            ->where('state', ScoreResultState::Scored)
            ->get();
        $own = $this->progress->experienceDiagnosis($main, $viewer)?->load('valueRows', 'treeRows');
        $enough = $materials->count() >= self::INDIVIDUAL_MINIMUM;

        return $this->rowsOf($main->diagnosis)
            ->filter(fn (DiagnosisRow $row) => in_array($row->kind, [DiagnosisRowKind::Value, DiagnosisRowKind::TreeElement], true))
            ->map(function (DiagnosisRow $row) use ($scores, $own, $enough): array {
                if ($row->kind === DiagnosisRowKind::TreeElement) {
                    return $this->row($row, $row->score, $row->score_state, $own?->treeRows->firstWhere('tree_element', $row->tree_element)?->experience);
                }

                $mean = ScoreMath::round1(ScoreMath::mean($scores->where('value_id', $row->value_id)->pluck('score')->map(fn ($score) => (float) $score)->values()->all()));
                $state = match (true) {
                    ! $enough => ScoreState::InsufficientData,
                    $mean === null => ScoreState::NoTrace,
                    default => ScoreState::Scored,
                };

                return $this->row($row, $enough ? $mean : null, $state, $own?->valueRows->firstWhere('value_id', $row->value_id)?->experience);
            })
            ->values()
            ->all();
    }

    /**
     * The organization's published main diagnoses, the latest per team from the
     * last 12 months; the common rows only, no process block (18 · 18.5).
     *
     * @return list<array<string, mixed>>
     */
    public function organization(Organization $organization): array
    {
        $mains = MainDiagnosis::query()
            ->where('stage', MainDiagnosisStage::Published)
            ->whereHas('diagnosis', fn ($query) => $query
                ->where('organization_id', $organization->id)
                ->where('status', DiagnosisStatus::Published))
            ->where('published_at', '>=', now()->subMonths(12))
            // One person inside a company is not the organization (18 · 18.5).
            ->where(fn ($query) => $query->where('subject', '!=', MainDiagnosisSubject::Person->value))
            ->with(['diagnosis.rows.value', 'diagnosis.rows.area'])
            ->latest('published_at')
            ->get()
            ->unique(fn (MainDiagnosis $main) => $main->diagnosis->team_id ?? 'diagnosis-'.$main->id);

        $rows = $mains->flatMap(fn (MainDiagnosis $main) => $main->diagnosis->rows
            ->reject(fn (DiagnosisRow $row) => $row->kind === DiagnosisRowKind::Process)
            ->map(fn (DiagnosisRow $row) => ['row' => $row, 'main' => $main]));

        return $rows
            ->groupBy(fn (array $item) => $this->commonKey($item['row']))
            ->map(function (Collection $items) {
                $first = $items->first()['row'];
                $scores = $items->pluck('row')->filter(fn (DiagnosisRow $row) => $row->score_state === ScoreState::Scored && $row->score !== null)->map(fn (DiagnosisRow $row) => (float) $row->score)->values()->all();
                $experiences = $items
                    ->filter(fn (array $item) => $this->threshold->showsTeamExperience($item['main']))
                    ->pluck('row')
                    ->pluck('experience')
                    ->filter(fn ($value) => $value !== null)
                    ->map(fn ($value) => (float) $value)
                    ->values()
                    ->all();
                $mean = ScoreMath::round1(ScoreMath::mean($scores));

                return [
                    ...$this->row($first, $mean, $mean === null ? ScoreState::NoTrace : ScoreState::Scored, ScoreMath::round1(ScoreMath::mean($experiences))),
                    'diagnoses' => $items->count(),
                ];
            })
            ->sortBy(fn (array $row) => [array_search($row['kind'], ['value', 'area', 'tree_element'], true), $row['label']])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, DiagnosisRow>
     */
    private function rowsOf(Diagnosis $diagnosis): Collection
    {
        return $diagnosis->rows()->with(['value', 'process', 'area'])->orderBy('position')->get();
    }

    /**
     * Values are joined by name, regardless of version (18 · 18.5).
     */
    private function commonKey(DiagnosisRow $row): string
    {
        return match ($row->kind) {
            DiagnosisRowKind::Value => 'value:'.mb_strtolower(trim($row->label())),
            DiagnosisRowKind::Area => 'area:'.$row->area_id,
            DiagnosisRowKind::Process => 'process:'.$row->process_id,
            default => 'tree:'.$row->tree_element?->value,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function row(DiagnosisRow $row, ?float $score, ?ScoreState $state, ?float $experience): array
    {
        $gap = $score !== null && $experience !== null && $state === ScoreState::Scored ? round($experience - $score, 1) : null;

        return [
            'kind' => $row->kind->value,
            'key' => $this->commonKey($row),
            'label' => $row->label(),
            'score' => $score,
            'score_state' => $state?->value,
            'experience' => $experience,
            'gap' => $gap,
        ];
    }
}
