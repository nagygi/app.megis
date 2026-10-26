<?php

namespace App\Services\Diagnoses;

use App\Enums\ConsentKind;
use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialStatus;
use App\Enums\ParticipantRole;
use App\Enums\ScoreResultState;
use App\Enums\ScoreState;
use App\Enums\TreeElement;
use App\Models\Area;
use App\Models\Consent;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisType;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\MaterialValueScore;
use App\Models\OccasionExperience;
use App\Models\Process;
use App\Models\ProcessOccasion;
use App\Models\RawDetection;
use App\Services\Capabilities\CapabilityAssessmentService;
use App\Services\Inbox\InboxService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Recalculates a main diagnosis from its materials (13, 18): one Folyamat
 * level subdiagnosis per measured process, then the rows of the four blocks,
 * the ring, the step and the biggest gap. Runs until publishing; a row
 * written by hand keeps its value, only the automatic one is updated next to it.
 */
class DiagnosisCalculator
{
    public function recalculate(MainDiagnosis $main): void
    {
        $main->loadMissing('diagnosis.organization', 'valueSetVersion');

        // Published diagnoses do not change (13 · 13.4, 21 · 21.3).
        if (in_array($main->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true) || $main->valueSetVersion === null) {
            return;
        }

        $snapshot = $main->valueSetVersion->snapshot;
        $materials = $this->materials($main);
        $scores = MaterialValueScore::query()
            ->whereIn('material_id', $materials->pluck('id'))
            ->whereNull('speaker')
            ->get()
            ->groupBy('material_id');

        DB::transaction(function () use ($main, $snapshot, $materials, $scores): void {
            $processDiagnoses = $this->processDiagnoses($main, $snapshot, $materials, $scores);
            $this->mainRows($main, $snapshot, $materials, $scores, $processDiagnoses);
            $this->summarize($main);
        });

        // The capabilities read the same materials, e.g. without a peer who withdrew (NM-12).
        app(CapabilityAssessmentService::class)->refresh($main->diagnosis);
    }

    /**
     * The materials of the diagnosis (18 · 18.4): occasions, unclassified and
     * "Egyéb" ones, mirrors and attached uploads, without those of peers who
     * withdrew their consent (25 · 25.6).
     *
     * @return Collection<int, Material>
     */
    public function materials(MainDiagnosis $main): Collection
    {
        return Material::query()
            ->where('parent_diagnosis_id', $main->diagnosis_id)
            ->where('status', MaterialStatus::Processed)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhereNotIn('user_id', $this->withdrawnUserIds($main)))
            ->with('occasion')
            ->get();
    }

    /**
     * @return list<int>
     */
    public function withdrawnUserIds(MainDiagnosis $main): array
    {
        return $main->participants()
            ->where('role', ParticipantRole::Peer)
            ->whereNotNull('started_at')
            ->get()
            ->filter(fn ($participant) => ! Consent::query()
                ->where('user_id', $participant->user_id)
                ->where('main_diagnosis_id', $main->id)
                ->where('kind', ConsentKind::Answers)
                ->whereNull('revoked_at')
                ->exists())
            ->pluck('user_id')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, Collection<int, MaterialValueScore>>  $scores
     * @return Collection<int, Diagnosis> process id => its Folyamat level diagnosis
     */
    private function processDiagnoses(MainDiagnosis $main, array $snapshot, Collection $materials, Collection $scores): Collection
    {
        $processes = $main->processes()->get();

        if ($processes->isEmpty()) {
            $processes = Process::query()->whereBelongsTo($main->diagnosis->organization)->measured()->get();
        }

        return $processes->mapWithKeys(fn (Process $process) => [
            $process->id => $this->processDiagnosis($main, $process, $snapshot, $materials, $scores),
        ]);
    }

    /**
     * One Folyamat level diagnosis (13): rows from the occasions' scores, the
     * process point, the experience and the effectiveness.
     *
     * @param  array<string, mixed>  $snapshot
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, Collection<int, MaterialValueScore>>  $scores
     */
    private function processDiagnosis(MainDiagnosis $main, Process $process, array $snapshot, Collection $materials, Collection $scores): Diagnosis
    {
        $processIds = [$process->id, ...$process->children()->pluck('id')->all()];
        $occasionMaterials = $materials
            ->filter(fn (Material $material) => $material->occasion !== null && in_array($material->occasion->process_id, $processIds, true))
            ->groupBy('process_occasion_id');
        $occasions = ProcessOccasion::query()->whereIn('id', $occasionMaterials->keys())->orderBy('occurred_at')->get();
        $experiences = OccasionExperience::query()
            ->whereIn('process_occasion_id', ProcessOccasion::query()->whereIn('process_id', $processIds)->select('id'))
            ->whereNotNull('submitted_at')
            ->whereNotIn('user_id', $this->withdrawnUserIds($main))
            ->get();

        $minimum = $main->isSnapshot() ? 1 : 2;
        $diagnosis = $this->processDiagnosisRecord($main, $process, $occasions);
        $rowScores = [];

        foreach ($snapshot['values'] ?? [] as $position => $item) {
            $perOccasion = $occasions->map(fn (ProcessOccasion $occasion) => ScoreMath::mean(
                $occasionMaterials->get($occasion->id, collect())
                    ->flatMap(fn (Material $material) => $scores->get($material->id, collect()))
                    ->where('value_id', $item['value_id'])
                    ->where('state', ScoreResultState::Scored)
                    ->pluck('score')
                    ->map(fn ($score) => (float) $score)
                    ->values()
                    ->all(),
            ))->filter(fn ($score) => $score !== null)->values()->all();

            $mean = ScoreMath::mean($perOccasion);
            $experience = $this->experienceOf($experiences, 'value:'.$item['key'], $main);

            if ($mean !== null) {
                $rowScores[] = $mean;
            }

            $this->writeRow($diagnosis, ['kind' => DiagnosisRowKind::Value, 'value_id' => $item['value_id']], [
                'value_version_id' => $item['source_version_id'] ?? null,
                'position' => $position,
            ], $mean, [
                'n' => count($perOccasion),
                'deviation' => ScoreMath::round1(ScoreMath::deviation($perOccasion)),
                ...$experience['stats'],
            ], $experience['value']);
        }

        $enough = $occasions->count() >= $minimum;
        $point = $enough ? ScoreMath::round1(ScoreMath::mean($rowScores)) : null;
        $pointState = match (true) {
            ! $enough => ScoreState::InsufficientData,
            $point === null => ScoreState::NoTrace,
            default => ScoreState::Scored,
        };

        $effectiveness = collect(['yes', 'partial', 'no'])->mapWithKeys(fn (string $answer) => [
            $answer => $experiences->where('effectiveness', $answer)->count(),
        ])->all();

        $diagnosis->forceFill([
            'automatic_score' => $point,
            'automatic_score_state' => $pointState,
            ...($diagnosis->score_overridden_at === null ? ['score' => $point, 'score_state' => $pointState] : []),
            'aggregation' => [
                'kind' => 'process',
                'occasions' => $occasions->count(),
                'minimum' => $minimum,
                'single_occasion' => $occasions->count() === 1 && $main->isSnapshot(),
                'effectiveness' => $effectiveness,
                'used' => $occasions->map(fn (ProcessOccasion $occasion) => [
                    'id' => $occasion->id,
                    'date' => $occasion->occurred_at->toDateString(),
                    'process_id' => $occasion->process_id,
                    'materials' => $occasionMaterials->get($occasion->id)->count(),
                ])->all(),
                'skipped' => $this->skippedOccasions($main, $processIds),
                'calculated_at' => now()->toIso8601String(),
            ],
        ])->save();

        return $diagnosis;
    }

    /**
     * @param  Collection<int, ProcessOccasion>  $occasions
     */
    private function processDiagnosisRecord(MainDiagnosis $main, Process $process, Collection $occasions): Diagnosis
    {
        $parent = $main->diagnosis;
        $first = $occasions->first()?->occurred_at;
        $last = $occasions->last()?->occurred_at;
        $period = $first === null ? today()->format('Y.m.d.') : ($first->isSameDay($last) ? $first->format('Y.m.d.') : $first->format('Y.m.d.').' – '.$last->format('Y.m.d.'));

        $attributes = [
            'name' => mb_substr($process->name.' · '.$period, 0, 255),
            'diagnosed_on' => ($first ?? today())->toDateString(),
            'diagnosed_until' => $last !== null && ! $first->isSameDay($last) ? $last->toDateString() : null,
            'value_set_version_id' => $main->value_set_version_id,
        ];

        $diagnosis = Diagnosis::query()
            ->where('parent_id', $parent->id)
            ->where('level', DiagnosisLevel::Process)
            ->where('process_id', $process->id)
            ->first();

        if ($diagnosis !== null) {
            $diagnosis->update($attributes);

            return $diagnosis;
        }

        return app(DiagnosisService::class)->create([
            ...$attributes,
            'organization_id' => $parent->organization_id,
            'level' => DiagnosisLevel::Process,
            'process_id' => $process->id,
            'diagnosis_type_id' => DiagnosisType::byKey(DiagnosisType::PROCESS)->id,
            'parent_id' => $parent->id,
        ], DiagnosisCreator::Automatic, $parent->status);
    }

    /**
     * Occasions with materials that were not used, and why (13 · 13.5).
     *
     * @param  list<int>  $processIds
     * @return list<array{date: string, reason: string}>
     */
    private function skippedOccasions(MainDiagnosis $main, array $processIds): array
    {
        return Material::query()
            ->where('parent_diagnosis_id', $main->diagnosis_id)
            ->whereIn('status', [MaterialStatus::Failed, MaterialStatus::Pending, MaterialStatus::Transcribing, MaterialStatus::Analyzing])
            ->whereHas('occasion', fn ($query) => $query->whereIn('process_id', $processIds))
            ->with('occasion')
            ->get()
            ->map(fn (Material $material) => [
                'date' => $material->occasion->occurred_at->toDateString(),
                'reason' => $material->status === MaterialStatus::Failed ? 'failed' : 'processing',
            ])
            ->values()
            ->all();
    }

    /**
     * The four blocks of the main diagnosis (18 · 18.1).
     *
     * @param  array<string, mixed>  $snapshot
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, Collection<int, MaterialValueScore>>  $scores
     * @param  Collection<int, Diagnosis>  $processDiagnoses
     */
    private function mainRows(MainDiagnosis $main, array $snapshot, Collection $materials, Collection $scores, Collection $processDiagnoses): void
    {
        $diagnosis = $main->diagnosis;
        $experienceDiagnoses = $this->experienceDiagnoses($main);
        $situationExperiences = $this->situationExperiences($main);
        $position = 0;
        $keep = [];

        // Értékek: the mean of the materials' scores (18 · 18.4).
        foreach ($snapshot['values'] ?? [] as $item) {
            $perMaterial = $materials
                ->map(fn (Material $material) => $scores->get($material->id, collect())
                    ->first(fn (MaterialValueScore $score) => $score->value_id === $item['value_id'] && $score->state === ScoreResultState::Scored)
                    ?->score)
                ->filter(fn ($score) => $score !== null)
                ->map(fn ($score) => (float) $score)
                ->values()
                ->all();
            $experience = $this->teamExperience($main, $experienceDiagnoses, fn (Diagnosis $experienceDiagnosis) => $experienceDiagnosis->valueRows->firstWhere('value_id', $item['value_id']));
            $situations = ScoreMath::mean(ScoreMath::numericAnswers($situationExperiences->map(fn (OccasionExperience $answer) => $answer->answers['value:'.$item['key']] ?? null)));

            $row = $this->writeRow($diagnosis, ['kind' => DiagnosisRowKind::Value, 'value_id' => $item['value_id']], [
                'value_version_id' => $item['source_version_id'] ?? null,
                'position' => $position++,
            ], ScoreMath::mean($perMaterial), [
                'n' => count($perMaterial),
                'deviation' => ScoreMath::round1(ScoreMath::deviation($perMaterial)),
                ...$experience['stats'],
                'situations' => ScoreMath::round1($situations),
                // Without peers the gap is between the self-image and the situations (18 · 18.4).
                'self_gap' => ! $main->hasPeers() && $experience['value'] !== null && $situations !== null
                    ? ScoreMath::round1($experience['value'] - $situations)
                    : null,
            ], $experience['value']);
            $keep[] = $row->id;

            $this->tracesForValue($row, $materials, $scores, (int) $item['value_id'], $main);
        }

        // Folyamatok: the point of the process diagnosis (13 · 13.3).
        foreach ($processDiagnoses as $processId => $processDiagnosis) {
            $experience = ScoreMath::mean($processDiagnosis->valueRows()->pluck('experience')->filter(fn ($value) => $value !== null)->map(fn ($value) => (float) $value)->values()->all());

            $row = $this->writeRow($diagnosis, ['kind' => DiagnosisRowKind::Process, 'process_id' => $processId], [
                'position' => $position++,
            ], $processDiagnosis->score_state === ScoreState::InsufficientData ? null : $processDiagnosis->score, [
                'n' => $processDiagnosis->aggregation['occasions'] ?? 0,
                'insufficient' => $processDiagnosis->score_state === ScoreState::InsufficientData,
                'process_diagnosis_id' => $processDiagnosis->id,
            ], ScoreMath::round1($experience), insufficient: $processDiagnosis->score_state === ScoreState::InsufficientData);
            $keep[] = $row->id;

            $this->tracesForProcess($row, $materials, $scores, $processId, $main);
        }

        // Területek: scored by hand in the reading, Teljes only (18 · 18.1).
        if (! $main->isSnapshot()) {
            foreach (Area::query()->orderBy('position')->get() as $area) {
                $row = $diagnosis->rows()->firstOrCreate(
                    ['kind' => DiagnosisRowKind::Area, 'area_id' => $area->id],
                    ['position' => $position, 'assigned_on' => $diagnosis->diagnosed_on, 'score_state' => ScoreState::Pending],
                );
                $row->update(['position' => $position++]);
                $keep[] = $row->id;
            }
        }

        // A márkád alapja: the experience from V5 and T4, the PHI by hand (18 · 18.4).
        foreach (TreeElement::cases() as $element) {
            $experience = $this->teamExperience($main, $experienceDiagnoses, fn (Diagnosis $experienceDiagnosis) => $experienceDiagnosis->treeRows->firstWhere('tree_element', $element));
            $row = $diagnosis->rows()->firstOrCreate(
                ['kind' => DiagnosisRowKind::TreeElement, 'tree_element' => $element],
                ['position' => $position, 'assigned_on' => $diagnosis->diagnosed_on],
            );
            $row->forceFill([
                'position' => $position++,
                'experience' => ScoreMath::round1($experience['value']),
                'experience_state' => $experience['value'] === null ? null : 'answered',
                'aggregation_stats' => [...($row->aggregation_stats ?? []), ...$experience['stats']],
                'score_state' => $row->score_state ?? ScoreState::Pending,
            ])->save();
            $keep[] = $row->id;
        }

        $diagnosis->rows()->whereNotIn('id', $keep)->delete();
    }

    /**
     * Writes the calculated value of a row; a row written by hand keeps its
     * value and only the automatic one changes (13 · 13.4).
     *
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $stats
     */
    private function writeRow(Diagnosis $diagnosis, array $identity, array $attributes, ?float $score, array $stats, ?float $experience, bool $insufficient = false): DiagnosisRow
    {
        $row = $diagnosis->rows()->firstOrNew($identity);
        $state = match (true) {
            $insufficient => ScoreState::InsufficientData,
            $score === null => ScoreState::NoTrace,
            default => ScoreState::Scored,
        };
        $rounded = ScoreMath::round1($score);

        // An approved row whose calculated value changes waits again, with the reason in the log (20, question 5).
        if ($row->exists && $row->approved_at !== null && $row->overridden_at === null
            && ($row->automatic_score !== $rounded || $row->automatic_score_state !== $state)) {
            app(ReadingService::class)->log($diagnosis, $row, 'fell_back', null, [
                'from' => $row->automatic_score_state === ScoreState::Scored ? $row->automatic_score : '–',
                'to' => $state === ScoreState::Scored ? $rounded : '–',
            ]);
            $row->forceFill(['approved_at' => null, 'approved_by' => null]);
        }

        $row->forceFill([
            ...$attributes,
            'assigned_on' => $row->assigned_on ?? $diagnosis->diagnosed_on,
            'automatic_score' => $rounded,
            'automatic_score_state' => $state,
            ...($row->overridden_at === null ? ['score' => $rounded, 'score_state' => $state] : []),
            'experience' => ScoreMath::round1($experience),
            'experience_state' => $experience === null ? null : 'answered',
            // The % and the step count from the unrounded value (18 · 18.4).
            'aggregation_stats' => [...$stats, 'raw' => $score],
        ])->save();

        return $row;
    }

    /**
     * The Megélés subdiagnoses of the leader and the peers, without withdrawn ones (25 · 25.6).
     *
     * @return Collection<int, Diagnosis>
     */
    private function experienceDiagnoses(MainDiagnosis $main): Collection
    {
        return Diagnosis::query()
            ->where('parent_id', $main->diagnosis_id)
            ->where('diagnosis_type_id', DiagnosisType::byKey(DiagnosisType::EXPERIENCE)->id)
            ->whereNull('excluded_at')
            ->whereNotIn('user_id', $this->withdrawnUserIds($main))
            ->with(['valueRows', 'treeRows'])
            ->get();
    }

    /**
     * The V11·c answers of the diagnosis' occasions (12 · 12.4).
     *
     * @return Collection<int, OccasionExperience>
     */
    private function situationExperiences(MainDiagnosis $main): Collection
    {
        return OccasionExperience::query()
            ->where('main_diagnosis_id', $main->id)
            ->whereNotNull('submitted_at')
            ->whereNotIn('user_id', $this->withdrawnUserIds($main))
            ->get();
    }

    /**
     * The team experience: the leader and the peers with equal weight, the
     * leader as one person, without "nem tudom" (18 · 18.4).
     *
     * @param  Collection<int, Diagnosis>  $experienceDiagnoses
     * @param  callable(Diagnosis): ?DiagnosisRow  $rowOf
     * @return array{value: ?float, stats: array<string, mixed>}
     */
    private function teamExperience(MainDiagnosis $main, Collection $experienceDiagnoses, callable $rowOf): array
    {
        $leader = [];
        $peers = [];

        foreach ($experienceDiagnoses as $experienceDiagnosis) {
            $row = $rowOf($experienceDiagnosis);

            if ($row === null || $row->experience === null) {
                continue;
            }

            if ($experienceDiagnosis->user_id === $main->leader_id) {
                $leader[] = (float) $row->experience;
            } else {
                $peers[] = (float) $row->experience;
            }
        }

        $all = [...$leader, ...$peers];

        return [
            'value' => ScoreMath::mean($all),
            'stats' => [
                'leader' => ScoreMath::round1(ScoreMath::mean($leader)),
                'peers' => ScoreMath::round1(ScoreMath::mean($peers)),
                'peer_count' => count($peers),
            ],
        ];
    }

    /**
     * @param  Collection<int, OccasionExperience>  $experiences
     * @return array{value: ?float, stats: array<string, mixed>}
     */
    private function experienceOf(Collection $experiences, string $key, MainDiagnosis $main): array
    {
        $leader = ScoreMath::numericAnswers($experiences->where('user_id', $main->leader_id)->map(fn (OccasionExperience $answer) => $answer->answers[$key] ?? null));
        $peers = ScoreMath::numericAnswers($experiences->where('user_id', '!=', $main->leader_id)->map(fn (OccasionExperience $answer) => $answer->answers[$key] ?? null));

        return [
            'value' => ScoreMath::mean([...$leader, ...$peers]),
            'stats' => [
                'leader' => ScoreMath::round1(ScoreMath::mean($leader)),
                'leader_count' => count($leader),
                'peers' => ScoreMath::round1(ScoreMath::mean($peers)),
                'peer_count' => count($peers),
            ],
        ];
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, Collection<int, MaterialValueScore>>  $scores
     */
    private function tracesForValue(DiagnosisRow $row, Collection $materials, Collection $scores, int $valueId, MainDiagnosis $main): void
    {
        $this->syncTraces($row, $materials->flatMap(fn (Material $material) => $scores->get($material->id, collect())
            ->filter(fn (MaterialValueScore $score) => $score->value_id === $valueId && $score->state === ScoreResultState::Scored)
            ->flatMap(fn (MaterialValueScore $score) => collect($score->detection_ids)->map(fn (int $id) => ['detection' => $id, 'material' => $material]))), $main);
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, Collection<int, MaterialValueScore>>  $scores
     */
    private function tracesForProcess(DiagnosisRow $row, Collection $materials, Collection $scores, int $processId, MainDiagnosis $main): void
    {
        $processIds = [$processId, ...Process::query()->where('parent_id', $processId)->pluck('id')->all()];

        $this->syncTraces($row, $materials
            ->filter(fn (Material $material) => $material->occasion !== null && in_array($material->occasion->process_id, $processIds, true))
            ->flatMap(fn (Material $material) => $scores->get($material->id, collect())
                ->where('state', ScoreResultState::Scored)
                ->flatMap(fn (MaterialValueScore $score) => collect($score->detection_ids)->map(fn (int $id) => ['detection' => $id, 'material' => $material]))), $main);
    }

    /**
     * Keeps one trace per detection, so the claims' references stay (18 · 18.2, 20).
     *
     * @param  Collection<int, array{detection: int, material: Material}>  $sources
     */
    private function syncTraces(DiagnosisRow $row, Collection $sources, MainDiagnosis $main): void
    {
        $sources = $sources->unique('detection');
        $detections = RawDetection::query()->whereIn('id', $sources->pluck('detection'))->whereNull('excluded_at')->get()->keyBy('id');
        $keep = [];

        foreach ($sources as $source) {
            $detection = $detections->get($source['detection']);

            if ($detection === null) {
                continue;
            }

            $material = $source['material'];
            $trace = EvidenceTrace::query()->updateOrCreate(
                ['diagnosis_row_id' => $row->id, 'raw_detection_id' => $detection->id],
                [
                    'material_id' => $material->id,
                    'process_occasion_id' => $material->process_occasion_id,
                    'quote' => $detection->quote,
                    'source_type' => EvidenceTrace::sourceTypeFor($material),
                    'role' => $this->roleOf($main, $material->user_id),
                    'user_id' => $material->user_id,
                    'recorded_on' => ($material->occasion?->occurred_at ?? $material->created_at)->toDateString(),
                ],
            );
            $keep[] = $trace->id;
        }

        // Traces added or chosen by hand stay (20 · 20.2).
        $row->traces()->whereNotNull('raw_detection_id')->whereNull('created_by')->whereNotIn('id', $keep)->delete();
    }

    private function roleOf(MainDiagnosis $main, ?int $userId): string
    {
        return match (true) {
            $userId === null => 'external',
            $userId === $main->leader_id => 'leader',
            $main->participants()->where('user_id', $userId)->where('role', ParticipantRole::Peer)->exists() => 'peer',
            default => 'external',
        };
    }

    /**
     * The ring, the step, the points, the biggest gap and the threshold (18 · 18.3, 18.4, 18.6).
     * Also after a correction of a published diagnosis, from its rows only (21 · 21.4).
     */
    public function summarize(MainDiagnosis $main): void
    {
        $rows = $main->diagnosis->rows()->get();
        $scored = $rows->filter(fn (DiagnosisRow $row) => $row->score_state === ScoreState::Scored && $row->score !== null);
        // A row written by hand counts with its own value, a calculated one unrounded (18 · 18.4).
        $scores = $scored->map(fn (DiagnosisRow $row) => (float) ($row->overridden_at === null ? ($row->aggregation_stats['raw'] ?? $row->score) : $row->score))->values()->all();
        $gapOf = fn (DiagnosisRow $row) => $main->hasPeers() ? $row->gap() : ($row->aggregation_stats['self_gap'] ?? null);

        $gapRow = $rows
            ->filter(fn (DiagnosisRow $row) => in_array($row->kind, [DiagnosisRowKind::Value, DiagnosisRowKind::TreeElement], true) && $gapOf($row) !== null)
            ->sortByDesc(fn (DiagnosisRow $row) => abs($gapOf($row)))
            ->first()
            ?->loadMissing(['value', 'process', 'area']);

        $peersAnswered = $this->experienceDiagnoses($main)->where('user_id', '!=', $main->leader_id)->count();

        $main->forceFill([
            'calculation' => [
                'ring' => ScoreMath::ringPercent($scores),
                'step' => ScoreMath::step($scores),
                ...ScoreMath::points($scores),
                'measuring_points' => count($scores),
                'biggest_gap' => $gapRow === null ? null : [
                    'row_id' => $gapRow->id,
                    'label' => $gapRow->label(),
                    'experience' => $gapRow->experience,
                    'score' => $main->hasPeers() ? $gapRow->score : ($gapRow->aggregation_stats['situations'] ?? null),
                    'gap' => $gapOf($gapRow),
                ],
                'peers_answered' => $peersAnswered,
                'threshold_met' => ! $main->hasPeers() || $peersAnswered >= ThresholdPolicy::PEERS,
                // The state of the reading's AI drafts stays (18 · 18.3).
                'drafts' => $main->calculation['drafts'] ?? null,
            ],
            'calculated_at' => now(),
        ])->save();

        if ($main->calculation['threshold_met']) {
            app(InboxService::class)->closeAutomatically('threshold:'.$main->id);
        }
    }
}
