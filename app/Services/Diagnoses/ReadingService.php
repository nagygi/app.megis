<?php

namespace App\Services\Diagnoses;

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\ScoreState;
use App\Exceptions\DiagnosisException;
use App\Jobs\RecalculateMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\DiagnosisClaim;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Models\ReviewEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The reading (20): the 2nd gate. Rows, claims and texts are approved one by
 * one or a block at a time, written over with a reason, traces taken out,
 * highlighted or added; every step lands in the log (20 · 20.5). Nothing
 * reaches the report without approval.
 */
class ReadingService
{
    public function __construct(private DiagnosisService $diagnoses) {}

    /**
     * The order of the report's blocks (20 · 20.1).
     *
     * @return list<DiagnosisRowKind>
     */
    public static function blocks(): array
    {
        return [DiagnosisRowKind::Value, DiagnosisRowKind::Process, DiagnosisRowKind::Area, DiagnosisRowKind::TreeElement];
    }

    /**
     * @return Collection<int, DiagnosisRow>
     */
    public function rows(MainDiagnosis $main): Collection
    {
        $order = array_flip(array_map(fn (DiagnosisRowKind $kind) => $kind->value, self::blocks()));

        return $main->diagnosis->rows()
            ->with(['value', 'process', 'area', 'approver', 'overrider'])
            ->get()
            ->sortBy(fn (DiagnosisRow $row) => [$order[$row->kind->value] ?? 9, $row->position])
            ->values();
    }

    public function approveRow(DiagnosisRow $row, User $admin): void
    {
        $this->ensureOpen($row->diagnosis);

        if ($row->score_state === null || $row->score_state === ScoreState::Pending) {
            throw new DiagnosisException(__('admin.reading.score_first'));
        }

        $row->forceFill(['approved_at' => now(), 'approved_by' => $admin->id])->save();
        $this->log($row->diagnosis, $row, 'approved', $admin);
    }

    /**
     * Every row of the block not written over and not approved yet; each is logged (20 · 20.2).
     */
    public function approveBlock(MainDiagnosis $main, DiagnosisRowKind $kind, User $admin): int
    {
        $this->ensureOpen($main->diagnosis);

        $rows = $main->diagnosis->rows()
            ->where('kind', $kind)
            ->whereNull('approved_at')
            ->whereNull('overridden_at')
            ->whereNotNull('score_state')
            ->where('score_state', '!=', ScoreState::Pending)
            ->get();

        DB::transaction(function () use ($rows, $admin): void {
            foreach ($rows as $row) {
                $this->approveRow($row, $admin);
            }
        });

        return $rows->count();
    }

    /**
     * "Átírom": the PHI with a mandatory reason, shown to the leader in the
     * Melléklet (20 · 20.2, 28 · 28.2). A process row moves its process diagnosis too.
     */
    public function overrideRow(DiagnosisRow $row, string $score, string $reason, User $admin): void
    {
        $this->ensureOpen($row->diagnosis);

        DB::transaction(function () use ($row, $score, $reason, $admin): void {
            $previous = $row->scoreLabel();
            $this->diagnoses->overrideRow($row, $score, $reason, $admin);
            $row->refresh()->forceFill(['approved_at' => now(), 'approved_by' => $admin->id])->save();

            if ($row->kind === DiagnosisRowKind::Process && ($processDiagnosis = $this->processDiagnosisOf($row)) !== null) {
                $this->diagnoses->overrideScore($processDiagnosis, $score, $reason, $admin);
            }

            $this->log($row->diagnosis, $row, 'overridden', $admin, ['from' => $previous, 'to' => $row->scoreLabel(), 'reason' => trim($reason)]);
        });
    }

    /**
     * Areas and the tree: scored by hand, whole numbers, no reason (18 · 18.4, 20 · 20.2).
     */
    public function scoreRow(DiagnosisRow $row, string $score, User $admin): void
    {
        $this->ensureOpen($row->diagnosis);

        if (! in_array($row->kind, [DiagnosisRowKind::Area, DiagnosisRowKind::TreeElement], true)) {
            throw new DiagnosisException(__('admin.reading.score_by_hand_only'));
        }

        $row->forceFill([
            ...match ($score) {
                DiagnosisService::NO_TRACE => ['score' => null, 'score_state' => ScoreState::NoTrace],
                default => ['score' => (float) (int) $score, 'score_state' => ScoreState::Scored],
            },
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ])->save();

        $this->log($row->diagnosis, $row, 'scored', $admin, ['to' => $row->scoreLabel()]);
        RecalculateMainDiagnosis::dispatch($row->diagnosis->main);
    }

    /**
     * "Nincs elég adat": the row goes to the report without a PHI (20 · 20.2).
     */
    public function markInsufficient(DiagnosisRow $row, User $admin): void
    {
        $this->ensureOpen($row->diagnosis);

        $row->forceFill([
            'score' => null,
            'score_state' => ScoreState::InsufficientData,
            'override_reason' => $row->automatic_score_state === null ? null : __('admin.reading.insufficient_reason'),
            'overridden_by' => $row->automatic_score_state === null ? null : $admin->id,
            'overridden_at' => $row->automatic_score_state === null ? null : now(),
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ])->save();

        $this->log($row->diagnosis, $row, 'insufficient', $admin);
        RecalculateMainDiagnosis::dispatch($row->diagnosis->main);
    }

    /**
     * An approved row can be opened again until publishing (20 · 20.2).
     */
    public function reopenRow(DiagnosisRow $row, User $admin): void
    {
        $this->ensureOpen($row->diagnosis);

        $row->forceFill(['approved_at' => null, 'approved_by' => null])->save();
        $this->log($row->diagnosis, $row, 'reopened', $admin);
    }

    /**
     * Out of the report; the raw detection stays (20 · 20.2).
     */
    public function toggleTrace(EvidenceTrace $trace, User $admin): void
    {
        $diagnosis = $trace->row->diagnosis;
        $this->ensureOpen($diagnosis);

        $excluded = $trace->excluded_at === null;
        $trace->forceFill(['excluded_at' => $excluded ? now() : null, 'highlighted_at' => $excluded ? null : $trace->highlighted_at])->save();
        $this->log($diagnosis, $trace, $excluded ? 'excluded' : 'included', $admin);
    }

    /**
     * One trace per row is shown first on the chapter page (20 · 20.2).
     */
    public function highlightTrace(EvidenceTrace $trace, User $admin): void
    {
        $diagnosis = $trace->row->diagnosis;
        $this->ensureOpen($diagnosis);

        DB::transaction(function () use ($trace, $admin, $diagnosis): void {
            EvidenceTrace::query()->where('diagnosis_row_id', $trace->diagnosis_row_id)->update(['highlighted_at' => null]);
            $trace->forceFill(['highlighted_at' => now(), 'excluded_at' => null])->save();
            $this->log($diagnosis, $trace, 'highlighted', $admin);
        });
    }

    /**
     * A trace written by hand: the quote, the material, the date and the role (20, question 1).
     *
     * @param  array{quote: string, material_id: ?int, recorded_on: ?string, role: ?string, source_type: string}  $data
     */
    public function addTrace(DiagnosisRow $row, array $data, User $admin): EvidenceTrace
    {
        $this->ensureOpen($row->diagnosis);

        $trace = $row->traces()->create([
            'quote' => trim($data['quote']),
            'material_id' => $data['material_id'],
            'source_type' => $data['source_type'],
            'role' => $data['role'],
            'recorded_on' => $data['recorded_on'],
            'created_by' => $admin->id,
        ]);

        $this->log($row->diagnosis, $trace, 'added', $admin, ['row_id' => $row->id]);

        return $trace;
    }

    /**
     * Areas and the tree take their evidence from the diagnosis' traces (20 · 20.2).
     */
    public function copyTrace(DiagnosisRow $row, EvidenceTrace $source, User $admin): EvidenceTrace
    {
        $this->ensureOpen($row->diagnosis);
        abort_unless($source->row->diagnosis_id === $row->diagnosis_id, 404);

        $trace = $row->traces()->firstOrCreate(
            ['raw_detection_id' => $source->raw_detection_id, 'quote' => $source->quote],
            [
                'material_id' => $source->material_id,
                'process_occasion_id' => $source->process_occasion_id,
                'source_type' => $source->source_type,
                'role' => $source->role,
                'user_id' => $source->user_id,
                'recorded_on' => $source->recorded_on,
                'created_by' => $admin->id,
            ],
        );

        $this->log($row->diagnosis, $trace, 'added', $admin, ['row_id' => $row->id, 'from_trace_id' => $source->id]);

        return $trace;
    }

    /**
     * A claim written or rewritten by the admin; it becomes theirs and waits for approval (20 · 20.3).
     *
     * @param  array{diagnosis_row_id: int, kind: string, text: string, trace_ids: list<int>}  $data
     */
    public function saveClaim(MainDiagnosis $main, ?DiagnosisClaim $claim, array $data, User $admin): DiagnosisClaim
    {
        $this->ensureOpen($main->diagnosis);

        $row = $main->diagnosis->rows()->findOrFail($data['diagnosis_row_id']);
        $traceIds = EvidenceTrace::query()->where('diagnosis_row_id', $row->id)->whereIn('id', $data['trace_ids'])->pluck('id')->all();

        if ($traceIds === []) {
            throw new DiagnosisException(__('admin.reading.claims.trace_needed'));
        }

        $attributes = [
            'diagnosis_row_id' => $row->id,
            'kind' => $data['kind'],
            'text' => trim($data['text']),
            'trace_ids' => $traceIds,
            'status' => DiagnosisClaim::DRAFT,
            'ai_call_id' => null,
            'approved_at' => null,
            'approved_by' => null,
        ];

        if ($claim === null) {
            $claim = DiagnosisClaim::create(['diagnosis_id' => $main->diagnosis_id, ...$attributes]);
            $this->log($main->diagnosis, $claim, 'added', $admin);
        } else {
            $claim->update($attributes);
            $this->log($main->diagnosis, $claim, 'edited', $admin);
        }

        return $claim;
    }

    public function approveClaim(DiagnosisClaim $claim, User $admin): void
    {
        $this->ensureOpen($claim->row->diagnosis);

        $claim->update(['status' => DiagnosisClaim::APPROVED, 'approved_at' => now(), 'approved_by' => $admin->id]);
        $this->log($claim->row->diagnosis, $claim, 'approved', $admin);
    }

    public function deleteClaim(DiagnosisClaim $claim, User $admin): void
    {
        $diagnosis = $claim->row->diagnosis;
        $this->ensureOpen($diagnosis);

        $this->log($diagnosis, null, 'deleted', $admin, ['claim' => $claim->text, 'kind' => $claim->kind]);
        $claim->delete();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function saveText(MainDiagnosis $main, string $key, string $text, ?array $meta, User $admin): DiagnosisText
    {
        $this->ensureOpen($main->diagnosis);

        $existing = DiagnosisText::query()->where('diagnosis_id', $main->diagnosis_id)->where('key', $key)->first();
        $model = DiagnosisText::query()->updateOrCreate(
            ['diagnosis_id' => $main->diagnosis_id, 'key' => $key],
            ['text' => trim($text), 'meta' => $meta, 'status' => ReadingDraftService::DRAFT, 'ai_call_id' => null, 'approved_at' => null, 'approved_by' => null],
        );

        $this->log($main->diagnosis, $model, $existing === null ? 'added' : 'edited', $admin);

        return $model;
    }

    public function approveText(DiagnosisText $text, User $admin): void
    {
        $diagnosis = Diagnosis::query()->findOrFail($text->diagnosis_id);
        $this->ensureOpen($diagnosis);

        $text->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => $admin->id]);
        $this->log($diagnosis, $text, 'approved', $admin);
    }

    /**
     * "A legnagyobb rés": the calculated one, or another row chosen (20 · 20.4).
     */
    public function approveGap(MainDiagnosis $main, ?int $rowId, User $admin): void
    {
        $this->ensureOpen($main->diagnosis);

        if ($rowId !== null) {
            $main->diagnosis->rows()->whereIn('kind', [DiagnosisRowKind::Value, DiagnosisRowKind::TreeElement])->findOrFail($rowId);
        }

        $main->update(['biggest_gap_row_id' => $rowId, 'biggest_gap_approved_at' => now(), 'biggest_gap_approved_by' => $admin->id]);
        $this->log($main->diagnosis, $main, 'gap_approved', $admin, ['row_id' => $rowId ?? ($main->calculation['biggest_gap']['row_id'] ?? null)]);
    }

    /**
     * The row shown as the biggest gap: the admin's choice, or the calculated one.
     */
    public function gapRow(MainDiagnosis $main): ?DiagnosisRow
    {
        $id = $main->biggest_gap_row_id ?? ($main->calculation['biggest_gap']['row_id'] ?? null);

        return $id === null ? null : $main->diagnosis->rows()->with('value')->find($id);
    }

    /**
     * The texts the reading needs: the snapshot has no area block (18 · 18.3).
     *
     * @return list<string>
     */
    public function requiredTexts(MainDiagnosis $main): array
    {
        return array_values(array_filter(DiagnosisText::KEYS, fn (string $key) => ! ($key === 'summary_areas' && $main->isSnapshot())
            && ! ($key === 'summary_processes' && ! $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Process)->exists())));
    }

    /**
     * "14 / 19 jóváhagyva" and whether the Publikálás tab is open (20 · 20.1, 20.5).
     *
     * @return array{rows: int, rows_total: int, claims: int, claims_total: int, texts: int, texts_total: int, gap: bool, complete: bool}
     */
    public function progress(MainDiagnosis $main): array
    {
        $rows = $main->diagnosis->rows()->get(['id', 'approved_at']);
        $claims = DiagnosisClaim::query()->where('diagnosis_id', $main->diagnosis_id)->get(['id', 'status']);
        $required = $this->requiredTexts($main);
        $texts = DiagnosisText::query()->where('diagnosis_id', $main->diagnosis_id)->whereIn('key', $required)->whereNotNull('approved_at')->count();
        $gap = $main->biggest_gap_approved_at !== null || ($main->calculation['biggest_gap'] ?? null) === null;

        $result = [
            'rows' => $rows->whereNotNull('approved_at')->count(),
            'rows_total' => $rows->count(),
            'claims' => $claims->where('status', DiagnosisClaim::APPROVED)->count(),
            'claims_total' => $claims->count(),
            'texts' => $texts,
            'texts_total' => count($required),
            'gap' => $gap,
        ];

        return [...$result, 'complete' => $result['rows'] === $result['rows_total'] && $result['rows_total'] > 0
            && $result['claims'] === $result['claims_total']
            && $result['texts'] === $result['texts_total']
            && $gap];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(Diagnosis $diagnosis, ?Model $subject, string $action, ?User $actor, array $payload = []): ReviewEvent
    {
        $event = new ReviewEvent(['diagnosis_id' => $diagnosis->id, 'action' => $action, 'actor_id' => $actor?->id, 'payload' => $payload === [] ? null : $payload]);
        $event->subject()->associate($subject);
        $event->save();

        return $event;
    }

    /**
     * The reading is open from its start; after publishing only the 21 · 21.4 corrections remain.
     */
    private function ensureOpen(Diagnosis $diagnosis): void
    {
        $main = $diagnosis->main ?? $diagnosis->parent?->main;

        if ($main === null || $main->stage->isBeforeRunning() || $main->stage === MainDiagnosisStage::Closed) {
            throw new DiagnosisException(__('admin.reading.closed'));
        }
    }

    private function processDiagnosisOf(DiagnosisRow $row): ?Diagnosis
    {
        return Diagnosis::query()
            ->where('parent_id', $row->diagnosis_id)
            ->where('level', DiagnosisLevel::Process)
            ->where('process_id', $row->process_id)
            ->first();
    }
}
