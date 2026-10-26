<?php

namespace App\Services\Report;

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\DiagnosisStatus;
use App\Enums\MaterialContext;
use App\Enums\ScoreState;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\EvidenceTrace;
use App\Models\Material;
use App\Services\Diagnoses\ScoreMath;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The report of a standalone diagnosis (04 · 4.6, 06 · 6.5): the view-model
 * of the main diagnosis' report (ReportBuilder) from the rows alone. There
 * is no reading, so no claims; at the end the mégis' recommendation. Built
 * live: a published diagnosis no longer changes.
 */
class StandaloneReportBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Diagnosis $diagnosis): array
    {
        $diagnosis->loadMissing(['organization', 'team', 'user', 'process', 'type', 'material', 'valueSetVersion']);
        // The four brand base rows are always there; an empty one is no measurement.
        $rows = $diagnosis->rows()->with(['value', 'valueVersion', 'process', 'area'])->get()
            ->filter(fn (DiagnosisRow $row) => $row->score_state !== null || $row->experience !== null)
            ->values();
        $definitions = collect($diagnosis->valueSetVersion?->snapshot['values'] ?? [])->keyBy('value_id');
        $source = $diagnosis->material === null ? null : EvidenceTrace::sourceTypeFor($diagnosis->material);
        $gapRow = $rows
            ->filter(fn (DiagnosisRow $row) => in_array($row->kind, [DiagnosisRowKind::Value, DiagnosisRowKind::TreeElement], true) && $row->gap() !== null)
            ->sortByDesc(fn (DiagnosisRow $row) => abs($row->gap()))
            ->first();

        $rowData = $rows->map(fn (DiagnosisRow $row) => $this->row($row, $diagnosis, $definitions, $source, $gapRow))->values();

        $chapters = collect(ReportBuilder::CHAPTERS)
            ->map(fn (DiagnosisRowKind $kind, string $chapter) => ReportBuilder::chapter($chapter, $rowData->where('kind', $kind->value)->values()))
            ->filter(fn (array $chapter) => $chapter['rows'] !== [])
            ->all();

        $scores = $rows->filter(fn (DiagnosisRow $row) => $row->score_state === ScoreState::Scored && $row->score !== null)
            ->map(fn (DiagnosisRow $row) => (float) $row->score)
            ->values()
            ->all();
        $recommendation = DiagnosisText::query()->where('diagnosis_id', $diagnosis->id)->where('key', 'recommendation')->whereNotNull('approved_at')->first();
        $subjectName = $this->subjectName($diagnosis);
        $publishedOn = $this->publishedOn($diagnosis)->toDateString();

        return [
            'meta' => [
                'main_id' => null,
                'diagnosis_id' => $diagnosis->id,
                'name' => $diagnosis->name,
                'subject' => $diagnosis->level->value,
                'subject_name' => $subjectName,
                'organization' => $diagnosis->organization->name,
                'team' => $diagnosis->team?->name,
                'leader' => $diagnosis->user?->name,
                'product' => $diagnosis->type->name,
                'snapshot' => false,
                'standalone' => true,
                // A user's own diagnosis speaks to one person.
                'voice' => $diagnosis->level === DiagnosisLevel::User ? 'self' : 'team',
                'has_peers' => false,
                'starts_on' => $diagnosis->diagnosed_on->toDateString(),
                'published_on' => $publishedOn,
                'peers' => 0,
                'peers_answered' => 0,
                'shows_experience' => true,
                'missing_peers' => 0,
                'threshold_overridden' => false,
            ],
            'ring' => [
                'percent' => ScoreMath::ringPercent($scores),
                'step' => ScoreMath::step($scores),
                ...ScoreMath::points($scores),
                'measuring_points' => count($scores),
            ],
            'chapters' => $chapters,
            'rows' => $rowData->all(),
            'biggest_gap' => $gapRow === null ? null : $rowData->firstWhere('id', $gapRow->id),
            'strength' => null,
            'crack' => null,
            'texts' => [
                'recommendation' => $recommendation === null ? null : ['text' => $recommendation->text, 'row' => null, 'from' => null, 'to' => null],
            ],
            'processes' => ['measured' => [], 'others' => []],
            'views' => ['team' => [], 'organization' => [], 'individual' => []],
            'appendix' => [
                'who' => ['leader' => $subjectName, 'subject' => $subjectName, 'peers' => 0, 'answered' => 0],
                'sources' => $this->sourceCounts($diagnosis->material),
                'source' => $this->source($diagnosis),
                'window' => $this->window($diagnosis),
                'read_by' => ['published_on' => $publishedOn],
                'quotes' => $rowData->flatMap(fn (array $row) => collect($row['traces'])->map(fn (array $trace) => [...$trace, 'row' => $row['label']]))
                    ->unique('quote')
                    ->values()
                    ->all(),
                'claims' => ['counts' => ['strength' => 0, 'crack' => 0, 'hunch' => 0], 'list' => []],
                'rows' => $rowData->map(fn (array $row) => ['label' => $row['label'], 'kind' => $row['kind'], 'score' => $row['score'], 'score_state' => $row['score_state']])->all(),
                'overrides' => $rows->whereNotNull('overridden_at')->map(fn (DiagnosisRow $row) => [
                    'label' => $row->label(),
                    'from' => $row->automatic_score_state === ScoreState::Scored ? (float) $row->automatic_score : null,
                    'to' => $row->score_state === ScoreState::Scored ? (float) $row->score : null,
                    'reason' => $row->override_reason,
                    'date' => $row->overridden_at->toDateString(),
                ])->values()->all(),
            ],
            'takeaways' => [],
        ];
    }

    /**
     * The keys of ReportBuilder's row; the quotes of the row are its traces.
     *
     * @param  Collection<int|string, mixed>  $definitions
     * @return array<string, mixed>
     */
    private function row(DiagnosisRow $row, Diagnosis $diagnosis, Collection $definitions, ?string $source, ?DiagnosisRow $gapRow): array
    {
        $score = $row->score_state === ScoreState::Scored ? (float) $row->score : null;
        $experience = in_array($row->kind, [DiagnosisRowKind::Value, DiagnosisRowKind::TreeElement], true) ? $row->experience : null;
        $gap = $score !== null && $experience !== null ? round($experience - $score, 1) : null;

        return [
            'id' => $row->id,
            'kind' => $row->kind->value,
            'chapter' => array_search($row->kind, ReportBuilder::CHAPTERS, true),
            'label' => $row->label(),
            'element' => $row->tree_element?->value,
            'score' => $score,
            'score_state' => $row->score_state?->value,
            'score_whole' => HungarianNumber::rowValue($score),
            'experience' => $experience,
            'experience_whole' => HungarianNumber::rowValue($experience),
            'gap' => $gap,
            'gap_against' => $score,
            'gap_against_whole' => HungarianNumber::rowValue($score),
            'leader' => null,
            'peers' => null,
            'peer_count' => 0,
            'situations' => null,
            'n' => $row->aggregation_stats['n'] ?? null,
            'is_biggest_gap' => $gapRow?->id === $row->id,
            'has_crack' => false,
            'definition' => $row->kind === DiagnosisRowKind::Value ? $this->definition($row, $definitions) : null,
            'what_we_look_at' => $row->kind === DiagnosisRowKind::Area ? $row->area?->what_we_look_at : null,
            'occasions' => null,
            'override' => $row->overridden_at === null ? null : [
                'from' => $row->automatic_score_state === ScoreState::Scored ? (float) $row->automatic_score : null,
                'reason' => $row->override_reason,
                'date' => $row->overridden_at->toDateString(),
            ],
            'claims' => [],
            'traces' => collect($row->quotes ?? [])
                ->filter(fn (array $quote) => filled($quote['quote'] ?? null))
                ->map(fn (array $quote) => [
                    'quote' => $quote['quote'],
                    'source' => $source ?? EvidenceTrace::SOURCE_DOCUMENT,
                    'role' => null,
                    'date' => $diagnosis->diagnosed_on->toDateString(),
                    'highlighted' => false,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * The organization's own words when the diagnosis answered them (08 · 8.4),
     * otherwise the Értéktár version bound to the row (04 · 4.4).
     *
     * @param  Collection<int|string, mixed>  $definitions
     * @return array{meaning: ?string, low: ?string, high: ?string}
     */
    private function definition(DiagnosisRow $row, Collection $definitions): array
    {
        $own = $definitions->get($row->value_id);

        if ($own !== null) {
            return ['meaning' => $own['meaning'] ?? null, 'low' => $own['low_example'] ?? null, 'high' => $own['high_example'] ?? null];
        }

        $value = $row->valueVersion?->snapshot ?? $row->value?->only(['definition', 'meanings', 'low_examples', 'high_examples']) ?? [];

        return [
            'meaning' => filled($value['definition'] ?? null) ? $value['definition'] : ($value['meanings'][0] ?? null),
            'low' => $value['low_examples'][0] ?? null,
            'high' => $value['high_examples'][0] ?? null,
        ];
    }

    private function subjectName(Diagnosis $diagnosis): string
    {
        return match ($diagnosis->level) {
            DiagnosisLevel::User => (string) $diagnosis->user?->name,
            DiagnosisLevel::Team => (string) $diagnosis->team?->name,
            DiagnosisLevel::Process => (string) $diagnosis->process?->name,
            default => $diagnosis->organization->name,
        };
    }

    /**
     * When the mégis published it last; in the admin's preview, today.
     */
    private function publishedOn(Diagnosis $diagnosis): CarbonInterface
    {
        if ($diagnosis->status !== DiagnosisStatus::Published) {
            return now();
        }

        return $diagnosis->transitions()->where('to_status', DiagnosisStatus::Published)->latest('id')->first()?->created_at ?? $diagnosis->updated_at;
    }

    /**
     * The counts of the main report's Melléklet (28 · 28.2), for the PDF.
     *
     * @return array{conversations: int, recordings: int, documents: int, mirrors: int, calendar: bool}
     */
    private function sourceCounts(?Material $material): array
    {
        $isMirror = $material !== null && $material->context->isMirror();
        $hasAudio = $material !== null && $material->source->hasAudio();

        return [
            'conversations' => 0,
            'recordings' => (int) ($material !== null && $hasAudio && ! $isMirror),
            'documents' => (int) ($material !== null && ! $hasAudio && ! $isMirror && $material->context !== MaterialContext::Dictation),
            'mirrors' => (int) $isMirror,
            'calendar' => false,
        ];
    }

    /**
     * "Miből": the material, the users' diagnoses taken together (04 · 4.5),
     * or what the mégis recorded by hand.
     *
     * @return array{kind: string, type?: string, count?: int}
     */
    private function source(Diagnosis $diagnosis): array
    {
        return match (true) {
            $diagnosis->material !== null => ['kind' => 'material', 'type' => EvidenceTrace::sourceTypeFor($diagnosis->material)],
            $diagnosis->aggregation !== null => ['kind' => 'aggregated', 'count' => (int) ($diagnosis->aggregation['usable_count'] ?? 0)],
            default => ['kind' => 'manual'],
        };
    }

    /**
     * @return array{from: ?string, to: ?string}
     */
    private function window(Diagnosis $diagnosis): array
    {
        if (isset($diagnosis->aggregation['window'])) {
            return ['from' => $diagnosis->aggregation['window']['from'] ?? null, 'to' => $diagnosis->aggregation['window']['to'] ?? null];
        }

        return ['from' => $diagnosis->diagnosed_on->toDateString(), 'to' => $diagnosis->referenceDate()->toDateString()];
    }
}
