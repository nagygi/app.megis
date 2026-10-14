<?php

namespace App\Services\Diagnoses;

use App\Ai\AiContext;
use App\Ai\Tasks\DraftClaimsTask;
use App\Ai\Tasks\DraftReadingTextsTask;
use App\Enums\DiagnosisRowKind;
use App\Models\DiagnosisClaim;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Services\Ai\AiGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The AI drafts of the reading (18 · 18.3): claims on the rows, each built
 * on the row's own traces, then the summary sentences, the reading of the
 * brand base and the recommendation. Drafts the admin already touched stay.
 */
class ReadingDraftService
{
    /**
     * Traces per row sent to the model, the most recent first.
     */
    private const TRACES_PER_ROW = 30;

    public const DRAFT = 'draft';

    public function __construct(
        private AiGateway $ai,
        private DiagnosisCalculator $calculator,
    ) {}

    public function draft(MainDiagnosis $main): void
    {
        $this->calculator->recalculate($main);
        $main->refresh()->loadMissing('diagnosis.organization');

        $rows = $main->diagnosis->rows()->with(['value', 'process', 'area', 'traces'])->orderBy('position')->get()->values();
        $refs = $rows->mapWithKeys(fn (DiagnosisRow $row, int $index) => ['R'.($index + 1) => $row]);
        $context = AiContext::forOrganization($main->diagnosis->organization, $main->diagnosis);

        // Without a single trace there is nothing to build on; the admin writes the reading.
        if ($rows->every(fn (DiagnosisRow $row) => $row->traces->isEmpty())) {
            $this->markDrafts($main, 'no_traces');

            return;
        }

        $claims = $this->draftClaims($main, $refs, $context);
        $this->draftTexts($main, $refs, $claims, $context);

        $this->markDrafts($main, 'ready');
    }

    public function failed(MainDiagnosis $main): void
    {
        $this->markDrafts($main, 'failed');
    }

    /**
     * The state of the drafts for the reading page: ready, failed or no_traces.
     */
    private function markDrafts(MainDiagnosis $main, string $status): void
    {
        $main->forceFill(['calculation' => [...($main->calculation ?? []), 'drafts' => ['status' => $status, 'at' => now()->toIso8601String()]]])->save();
    }

    /**
     * @param  Collection<string, DiagnosisRow>  $refs
     * @return Collection<int, DiagnosisClaim>
     */
    private function draftClaims(MainDiagnosis $main, Collection $refs, AiContext $context): Collection
    {
        $withTraces = $refs->filter(fn (DiagnosisRow $row) => $row->traces->isNotEmpty());

        if ($withTraces->isEmpty()) {
            return $main->diagnosis->claims()->get();
        }

        $result = $this->ai->run(new DraftClaimsTask(
            $refs->map(fn (DiagnosisRow $row, string $ref) => [...$this->rowInput($ref, $row), 'nyomok' => $this->tracesInput($row)])->values()->all(),
            $main->isSnapshot(),
        ), $context);

        return DB::transaction(function () use ($main, $refs, $result): Collection {
            // The admin's work stays: an edited or approved claim leaves the draft status (20).
            $main->diagnosis->claims()->where('status', self::DRAFT)->whereNotNull('ai_call_id')->delete();

            foreach ($result->structured['claims'] ?? [] as $claim) {
                $row = $refs->get((string) ($claim['row_ref'] ?? ''));
                $text = trim((string) ($claim['text'] ?? ''));

                if ($row === null || $text === '' || ! in_array($claim['kind'] ?? null, DiagnosisClaim::KINDS, true)) {
                    continue;
                }

                $traceIds = collect($claim['trace_refs'] ?? [])
                    ->map(fn ($ref) => (int) ltrim((string) $ref, 'T'))
                    ->intersect($row->traces->pluck('id'))
                    ->unique()
                    ->values()
                    ->all();

                // A claim needs at least one trace of its own row (18 · 18.3).
                if ($traceIds === []) {
                    continue;
                }

                DiagnosisClaim::create([
                    'diagnosis_id' => $main->diagnosis_id,
                    'diagnosis_row_id' => $row->id,
                    'kind' => $claim['kind'],
                    'text' => mb_substr($text, 0, 1000),
                    'status' => self::DRAFT,
                    'trace_ids' => $traceIds,
                    'ai_call_id' => $result->call->id,
                ]);
            }

            return $main->diagnosis->claims()->get();
        });
    }

    /**
     * @param  Collection<string, DiagnosisRow>  $refs
     * @param  Collection<int, DiagnosisClaim>  $claims
     */
    private function draftTexts(MainDiagnosis $main, Collection $refs, Collection $claims, AiContext $context): void
    {
        $rowRefs = $refs->mapWithKeys(fn (DiagnosisRow $row, string $ref) => [$row->id => $ref]);

        $result = $this->ai->run(new DraftReadingTextsTask(
            $refs->map(fn (DiagnosisRow $row, string $ref) => $this->rowInput($ref, $row))->values()->all(),
            $claims->map(fn (DiagnosisClaim $claim) => [
                'sor' => $rowRefs->get($claim->diagnosis_row_id),
                'fajta' => $claim->kind,
                'mondat' => $claim->text,
            ])->values()->all(),
            ! $main->isSnapshot(),
        ), $context);

        $answer = $result->structured ?? [];
        $recommendation = $answer['recommendation'] ?? [];
        $recommendationRow = $refs->get((string) ($recommendation['row_ref'] ?? ''));

        $texts = [
            'summary_values' => $answer['summary_values'] ?? null,
            'summary_processes' => $answer['summary_processes'] ?? null,
            'summary_areas' => $main->isSnapshot() ? null : ($answer['summary_areas'] ?? null),
            'summary_brand' => $answer['summary_brand'] ?? null,
            'brand_reading' => $answer['brand_reading'] ?? null,
            'recommendation' => $recommendation['text'] ?? null,
        ];

        DB::transaction(function () use ($main, $texts, $result, $recommendation, $recommendationRow): void {
            foreach ($texts as $key => $text) {
                $text = trim((string) $text);
                $existing = DiagnosisText::query()->where('diagnosis_id', $main->diagnosis_id)->where('key', $key)->first();

                if ($text === '' || ($existing !== null && $existing->status !== self::DRAFT)) {
                    continue;
                }

                DiagnosisText::query()->updateOrCreate(
                    ['diagnosis_id' => $main->diagnosis_id, 'key' => $key],
                    [
                        'text' => mb_substr($text, 0, $key === 'brand_reading' ? 600 : 1000),
                        'status' => self::DRAFT,
                        'ai_call_id' => $result->call->id,
                        'meta' => $key === 'recommendation' && $recommendationRow !== null ? [
                            'row_id' => $recommendationRow->id,
                            'from' => isset($recommendation['from']) ? (int) $recommendation['from'] : null,
                            'to' => isset($recommendation['to']) ? (int) $recommendation['to'] : null,
                        ] : null,
                    ],
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function rowInput(string $ref, DiagnosisRow $row): array
    {
        return [
            'kód' => $ref,
            'blokk' => match ($row->kind) {
                DiagnosisRowKind::Value => 'érték',
                DiagnosisRowKind::Process => 'folyamat',
                DiagnosisRowKind::Area => 'terület',
                default => 'a márkád alapja',
            },
            'név' => $row->label(),
            'phi' => $row->scoreLabel() === '' ? null : $row->scoreLabel(),
            'megélés' => $row->experience,
            'rés' => $row->gap(),
            'n' => $row->aggregation_stats['n'] ?? null,
        ];
    }

    /**
     * Quotes with source, role and date; never a name (18 · 18.2).
     *
     * @return list<array<string, mixed>>
     */
    private function tracesInput(DiagnosisRow $row): array
    {
        return $row->traces
            ->sortByDesc('recorded_on')
            ->take(self::TRACES_PER_ROW)
            ->map(fn (EvidenceTrace $trace) => [
                'kód' => 'T'.$trace->id,
                'idézet' => mb_substr($trace->quote, 0, 400),
                'forrás' => $trace->source_type,
                'szerep' => $trace->role,
                'dátum' => $trace->recorded_on?->toDateString(),
            ])
            ->values()
            ->all();
    }
}
