<?php

namespace App\Services\Report;

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\ProcessKind;
use App\Enums\ProcessSource;
use App\Enums\ScoreState;
use App\Models\Area;
use App\Models\Diagnosis;
use App\Models\DiagnosisClaim;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Membership;
use App\Models\Process;
use App\Models\User;
use App\Services\Diagnoses\ReadingService;
use App\Services\Diagnoses\ScoreMath;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\Diagnoses\ViewDataBuilder;
use Illuminate\Support\Collection;

/**
 * One view-model of the report for the web, the admin's preview and the PDF
 * (plan 3.6, 21 · 21.1): only approved numbers and sentences, the included
 * traces, the threshold applied. Frozen into report_snapshots at publishing.
 */
class ReportBuilder
{
    /**
     * The chapters of the report and their blocks (27, question 1).
     */
    public const CHAPTERS = [
        'values' => DiagnosisRowKind::Value,
        'processes' => DiagnosisRowKind::Process,
        'areas' => DiagnosisRowKind::Area,
        'brand' => DiagnosisRowKind::TreeElement,
    ];

    public function __construct(
        private ReadingService $reading,
        private ThresholdPolicy $threshold,
        private ViewDataBuilder $views,
        private TakeawayBuilder $takeaways,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(MainDiagnosis $main): array
    {
        $main->loadMissing(['diagnosis.organization', 'diagnosis.team', 'leader', 'valueSetVersion']);
        $diagnosis = $main->diagnosis;
        $rows = $this->reading->rows($main)->load('traces');
        $definitions = collect($main->valueSetVersion?->snapshot['values'] ?? [])->keyBy('value_id');
        $claims = DiagnosisClaim::query()->where('diagnosis_id', $diagnosis->id)->where('status', DiagnosisClaim::APPROVED)->orderBy('id')->get()->groupBy('diagnosis_row_id');
        $showsExperience = $this->threshold->showsTeamExperience($main);
        $gapRow = $this->reading->gapRow($main);
        $processDiagnoses = Diagnosis::query()->where('parent_id', $diagnosis->id)->where('level', DiagnosisLevel::Process)->get()->keyBy('process_id');
        $areas = Area::query()->get()->keyBy('id');

        $rowData = $rows->map(fn (DiagnosisRow $row) => $this->row($row, $main, $definitions, $claims->get($row->id, collect()), $showsExperience, $gapRow, $processDiagnoses, $areas))->values();

        $chapters = collect(self::CHAPTERS)
            ->map(fn (DiagnosisRowKind $kind, string $chapter) => self::chapter($chapter, $rowData->where('kind', $kind->value)->values()))
            // A diagnosis without processes or areas skips their chapter (27, question 2).
            ->filter(fn (array $chapter) => $chapter['rows'] !== [])
            ->all();

        $texts = DiagnosisText::query()->where('diagnosis_id', $diagnosis->id)->whereNotNull('approved_at')->get()->keyBy('key');
        $recommendation = $texts->get('recommendation');
        $recommendationRow = $recommendation?->meta['row_id'] ?? null;
        $peers = $main->peers()->count();

        return [
            'meta' => [
                'main_id' => $main->id,
                'diagnosis_id' => $diagnosis->id,
                'name' => $diagnosis->name,
                'subject' => $main->subject->value,
                'subject_name' => $main->subjectName(),
                'organization' => $diagnosis->organization->name,
                'team' => $diagnosis->team?->name,
                'leader' => $main->leader->name,
                'product' => $main->product_name,
                'snapshot' => $main->isSnapshot(),
                'has_peers' => $main->hasPeers(),
                'starts_on' => ($main->running_at ?? $main->starts_on)?->toDateString(),
                'published_on' => ($main->published_at ?? now())->toDateString(),
                'peers' => $peers,
                'peers_answered' => $this->threshold->peersAnswered($main),
                'shows_experience' => $showsExperience,
                'missing_peers' => $this->threshold->missingPeers($main),
                'threshold_overridden' => $this->threshold->isOverridden($main),
            ],
            'ring' => [
                'percent' => $main->calculation['ring'] ?? null,
                'points' => $main->calculation['points'] ?? 0,
                'max' => $main->calculation['max'] ?? 0,
                'measuring_points' => $main->calculation['measuring_points'] ?? 0,
                'step' => $main->calculation['step'] ?? null,
            ],
            'chapters' => $chapters,
            'rows' => $rowData->all(),
            // Below the threshold there is no gap, so no row is named (Extra inputok 1).
            'biggest_gap' => $gapRow === null || ! $showsExperience ? null : collect($rowData)->firstWhere('id', $gapRow->id),
            'strength' => $this->pick($rowData, 'strength'),
            'crack' => $this->pick($rowData, 'crack'),
            'texts' => [
                // A base collection: Eloquent's except() filters by primary key.
                ...$texts->toBase()->except('recommendation')->map(fn (DiagnosisText $text) => $text->text)->all(),
                'recommendation' => $recommendation === null ? null : [
                    'text' => $recommendation->text,
                    'row' => $recommendationRow === null ? null : collect($rowData)->firstWhere('id', $recommendationRow)['label'] ?? null,
                    'from' => $recommendation->meta['from'] ?? null,
                    'to' => $recommendation->meta['to'] ?? null,
                ],
            ],
            'processes' => $this->processMap($main),
            'views' => [
                'team' => $this->views->team($main),
                'organization' => $this->views->organization($diagnosis->organization),
                'individual' => $this->viewers($main)->mapWithKeys(fn (User $user) => [$user->id => $this->views->individual($main, $user)])->all(),
            ],
            'appendix' => $this->appendix($main, $rows, $rowData),
            // The peers' Útravaló, frozen with the report (29).
            'takeaways' => $this->takeaways->recipients($main)->mapWithKeys(fn (User $peer) => [$peer->id => $this->takeaways->build($main, $peer)])->all(),
        ];
    }

    /**
     * Who sees the report (27 · 27.1): the leader and the organization's
     * leaders; a person inside a company only themselves.
     *
     * @return Collection<int, User>
     */
    public function viewers(MainDiagnosis $main): Collection
    {
        $users = collect([$main->leader]);

        if ($main->subject->value !== 'person') {
            $leaders = Membership::query()
                ->where('organization_id', $main->diagnosis->organization_id)
                ->where('is_organization_leader', true)
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter();
            $users = $users->merge($leaders);
        }

        return $users->unique('id')->values();
    }

    /**
     * @param  Collection<int|string, mixed>  $definitions
     * @param  Collection<int, DiagnosisClaim>  $claims
     * @param  Collection<int|string, Diagnosis>  $processDiagnoses
     * @param  Collection<int|string, Area>  $areas
     * @return array<string, mixed>
     */
    private function row(DiagnosisRow $row, MainDiagnosis $main, Collection $definitions, Collection $claims, bool $showsExperience, ?DiagnosisRow $gapRow, Collection $processDiagnoses, Collection $areas): array
    {
        $stats = $row->aggregation_stats ?? [];
        $traces = $row->traces->whereNull('excluded_at')
            ->sortBy(fn (EvidenceTrace $trace) => [$trace->highlighted_at === null ? 1 : 0, -($trace->recorded_on?->timestamp ?? 0)])
            ->values();
        $score = $row->score_state === ScoreState::Scored ? (float) $row->score : null;
        $experience = $showsExperience && ! in_array($row->kind, [DiagnosisRowKind::Area, DiagnosisRowKind::Process], true) ? $row->experience : null;
        // Without peers the gap is between the self-image and the situations (18 · 18.4).
        $against = $main->hasPeers() ? $score : ($stats['situations'] ?? null);
        $gap = $against !== null && $experience !== null ? round($experience - $against, 1) : null;
        $definition = $row->kind === DiagnosisRowKind::Value ? $definitions->get($row->value_id) : null;
        $processDiagnosis = $row->kind === DiagnosisRowKind::Process ? $processDiagnoses->get($row->process_id) : null;
        $effectiveness = $processDiagnosis?->aggregation['effectiveness'] ?? null;

        return [
            'id' => $row->id,
            'kind' => $row->kind->value,
            'chapter' => array_search($row->kind, self::CHAPTERS, true),
            'label' => $row->label(),
            'element' => $row->tree_element?->value,
            'score' => $score,
            'score_state' => $row->score_state?->value,
            'score_whole' => HungarianNumber::rowValue($score),
            'experience' => $experience,
            'experience_whole' => HungarianNumber::rowValue($experience),
            'gap' => $gap,
            'gap_against' => $against,
            'gap_against_whole' => HungarianNumber::rowValue($against),
            'leader' => $showsExperience ? ($stats['leader'] ?? null) : null,
            'peers' => $showsExperience ? ($stats['peers'] ?? null) : null,
            'peer_count' => $stats['peer_count'] ?? 0,
            'situations' => $stats['situations'] ?? null,
            'n' => $stats['n'] ?? null,
            'is_biggest_gap' => $showsExperience && $gapRow?->id === $row->id,
            'has_crack' => $claims->contains('kind', 'crack'),
            'definition' => $definition === null ? null : [
                'meaning' => $definition['meaning'] ?? null,
                'low' => $definition['low_example'] ?? null,
                'high' => $definition['high_example'] ?? null,
            ],
            'what_we_look_at' => $row->kind === DiagnosisRowKind::Area ? $areas->get($row->area_id)?->what_we_look_at : null,
            'occasions' => $effectiveness === null ? null : [
                'count' => $processDiagnosis->aggregation['occasions'] ?? 0,
                ...$effectiveness,
            ],
            'override' => $row->overridden_at === null ? null : [
                'from' => $row->automatic_score_state === ScoreState::Scored ? (float) $row->automatic_score : null,
                'reason' => $row->override_reason,
                'date' => $row->overridden_at->toDateString(),
            ],
            'claims' => $claims->map(fn (DiagnosisClaim $claim) => [
                'kind' => $claim->kind,
                'text' => $claim->text,
                'traces' => $traces->whereIn('id', $claim->trace_ids ?? [])->map(fn (EvidenceTrace $trace) => $this->trace($trace))->values()->all(),
            ])->values()->all(),
            'traces' => $traces->map(fn (EvidenceTrace $trace) => $this->trace($trace))->all(),
        ];
    }

    /**
     * A trace for the client: source, role and date, never a name (18 · 18.2).
     *
     * @return array<string, mixed>
     */
    private function trace(EvidenceTrace $trace): array
    {
        return [
            'quote' => $trace->quote,
            'source' => $trace->source_type,
            'role' => $trace->role,
            'date' => $trace->recorded_on?->toDateString(),
            'highlighted' => $trace->highlighted_at !== null,
        ];
    }

    /**
     * A chapter of the report from its rows; the standalone report too.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function chapter(string $chapter, Collection $rows): array
    {
        $scores = $rows->pluck('score')->filter(fn ($score) => $score !== null)->values()->all();

        return [
            'key' => $chapter,
            'points' => ScoreMath::points($scores),
            'average' => ScoreMath::round1(ScoreMath::mean($scores)),
            // Highest PHI first inside the block (27 · 27.4).
            'rows' => $rows->sortByDesc(fn (array $row) => $row['score'] ?? -1)->values()->all(),
        ];
    }

    /**
     * The first approved claim of a kind, with its first quote (27 · 27.3, 27.4).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>|null
     */
    private function pick(Collection $rows, string $kind): ?array
    {
        foreach ($rows as $row) {
            foreach ($row['claims'] as $claim) {
                if ($claim['kind'] === $kind) {
                    return ['row' => $row['label'], 'chapter' => $row['chapter'], 'text' => $claim['text'], 'quote' => $claim['traces'][0]['quote'] ?? null];
                }
            }
        }

        return null;
    }

    /**
     * The process map of R3: measured ones and the others, with chips (27 · 27.6).
     *
     * @return array<string, mixed>
     */
    private function processMap(MainDiagnosis $main): array
    {
        $measured = $main->processes()->pluck('processes.id');
        $processes = Process::query()->whereBelongsTo($main->diagnosis->organization)->whereNull('parent_id')->where('kind', '!=', ProcessKind::OneOff)->orderBy('name')->get();

        return [
            'measured' => $processes->whereIn('id', $measured)->map(fn (Process $process) => ['name' => $process->name, 'in_calendar' => $process->source === ProcessSource::Calendar])->values()->all(),
            'others' => $processes->whereNotIn('id', $measured)->map(fn (Process $process) => ['name' => $process->name, 'in_calendar' => $process->source === ProcessSource::Calendar])->values()->all(),
        ];
    }

    /**
     * Melléklet (28 · 28.2): who, from what, when, who read it; the quotes, the
     * claims, the measuring points and what the mégis wrote over.
     *
     * @param  Collection<int, DiagnosisRow>  $rows
     * @param  Collection<int, array<string, mixed>>  $rowData
     * @return array<string, mixed>
     */
    private function appendix(MainDiagnosis $main, Collection $rows, Collection $rowData): array
    {
        $materials = Material::query()->where('parent_diagnosis_id', $main->diagnosis_id)->where('status', MaterialStatus::Processed)->get();
        $claims = $rowData->flatMap(fn (array $row) => collect($row['claims'])->map(fn (array $claim) => [...$claim, 'row' => $row['label'], 'chapter' => $row['chapter']]));

        return [
            'who' => [
                'leader' => $main->leader->name,
                'subject' => $main->subjectName(),
                'peers' => $main->peers()->count(),
                'answered' => $this->threshold->peersAnswered($main),
            ],
            'sources' => [
                'conversations' => $main->conversations()->where('held_at', '<=', now())->count(),
                'recordings' => $materials->filter(fn (Material $material) => $material->source->hasAudio() && ! $material->context->isMirror())->count(),
                'documents' => $materials->filter(fn (Material $material) => ! $material->source->hasAudio() && ! $material->context->isMirror() && ! in_array($material->context, [MaterialContext::Dictation, MaterialContext::CapabilityStory], true))->count(),
                'mirrors' => $materials->filter(fn (Material $material) => $material->context->isMirror())->count(),
                'calendar' => $main->diagnosis->organization->processes()->where('source', ProcessSource::Calendar)->exists(),
            ],
            'window' => ['from' => ($main->running_at ?? $main->starts_on)?->toDateString(), 'to' => $main->publishes_on->toDateString()],
            'read_by' => ['published_on' => ($main->published_at ?? now())->toDateString()],
            'quotes' => $rowData->flatMap(fn (array $row) => collect($row['traces'])->map(fn (array $trace) => [...$trace, 'row' => $row['label']]))
                ->unique('quote')
                ->sortBy('date')
                ->values()
                ->all(),
            'claims' => [
                'counts' => collect(['strength', 'crack', 'hunch'])->mapWithKeys(fn (string $kind) => [$kind => $claims->where('kind', $kind)->count()])->all(),
                'list' => $claims->map(fn (array $claim) => ['kind' => $claim['kind'], 'text' => $claim['text'], 'row' => $claim['row'], 'traces' => count($claim['traces'])])->values()->all(),
            ],
            'rows' => $rowData->map(fn (array $row) => ['label' => $row['label'], 'kind' => $row['kind'], 'score' => $row['score'], 'score_state' => $row['score_state']])->all(),
            'overrides' => $rows->whereNotNull('overridden_at')->map(fn (DiagnosisRow $row) => [
                'label' => $row->label(),
                'from' => $row->automatic_score_state === ScoreState::Scored ? (float) $row->automatic_score : null,
                'to' => $row->score_state === ScoreState::Scored ? (float) $row->score : null,
                'reason' => $row->override_reason,
                'date' => $row->overridden_at->toDateString(),
            ])->values()->all(),
        ];
    }
}
