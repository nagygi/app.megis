<?php

namespace App\Livewire\Admin\MainDiagnoses;

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\MainDiagnosisStage;
use App\Exceptions\DiagnosisException;
use App\Models\Diagnosis;
use App\Models\DiagnosisClaim;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\EvidenceTrace;
use App\Models\InboxItem;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\ReportFeedback;
use App\Models\ReviewEvent;
use App\Models\User;
use App\Services\Diagnoses\DiagnosisService;
use App\Services\Diagnoses\ProcessOccasionBreakdown;
use App\Services\Diagnoses\ReadingService;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\Inbox\InboxService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Olvasat (A3, 20): three columns, the measuring points, the evidence of the
 * chosen row and what the leader reads. Approve, write over with a reason,
 * curate the traces; then the claims and the texts of the reading.
 */
class Reading extends Component
{
    /**
     * A score or experience: 1–6 with at most one decimal.
     */
    private const NUMBER_PATTERN = '/^(?:[1-5](?:\.\d)?|6(?:\.0)?)$/';

    public Diagnosis $diagnosis;

    #[Url(as: 'sor')]
    public ?int $selectedRowId = null;

    /** "Beépítem" from the Beérkező opens the reading with the feedback next to it (22 · 22.2). */
    #[Url(as: 'visszajelzes')]
    public ?int $feedbackId = null;

    public bool $overriding = false;

    public string $overrideScore = '';

    public string $overrideReason = '';

    public string $handScore = '';

    /**
     * @var array{quote: string, material_id: ?string, recorded_on: string, role: string, source_type: string}
     */
    public array $trace = ['quote' => '', 'material_id' => null, 'recorded_on' => '', 'role' => 'leader', 'source_type' => 'document'];

    /**
     * Picking evidence for an area or a tree element (20 · 20.2).
     *
     * @var array{block: string, source: string, word: string}
     */
    public array $traceFilter = ['block' => '', 'source' => '', 'word' => ''];

    /** The claim being edited: an id, "new", or null. */
    public ?string $editingClaim = null;

    /**
     * @var array{diagnosis_row_id: ?string, kind: string, text: string, trace_ids: list<string>}
     */
    public array $claim = ['diagnosis_row_id' => null, 'kind' => 'strength', 'text' => '', 'trace_ids' => []];

    /**
     * @var array<string, string>
     */
    public array $texts = [];

    /**
     * @var array{row_id: ?string, from: ?string, to: ?string}
     */
    public array $recommendation = ['row_id' => null, 'from' => null, 'to' => null];

    public ?string $gapRowId = null;

    public function mount(Diagnosis $diagnosis): void
    {
        abort_if($diagnosis->main === null, 404);
        Gate::authorize('update', $diagnosis);

        $this->diagnosis = $diagnosis;
        $this->loadTexts();
        $this->gapRowId = ($diagnosis->main->biggest_gap_row_id ?? $diagnosis->main->calculation['biggest_gap']['row_id'] ?? null) === null
            ? null
            : (string) ($diagnosis->main->biggest_gap_row_id ?? $diagnosis->main->calculation['biggest_gap']['row_id']);
        $this->selectedRowId ??= app(ReadingService::class)->rows($diagnosis->main)->first(fn (DiagnosisRow $row) => $row->approved_at === null)?->id;
    }

    public function selectRow(int $rowId): void
    {
        $this->selectedRowId = $this->row($rowId)->id;
        $this->reset(['overriding', 'overrideScore', 'overrideReason', 'handScore']);
        $this->resetValidation();
    }

    /**
     * "Jóváhagyom": green, and on to the next row waiting (20 · 20.2).
     */
    public function approve(ReadingService $reading): void
    {
        $row = $this->selected();

        $this->attempt(function () use ($reading, $row): void {
            $reading->approveRow($row, $this->admin());
            $this->selectNext($row);
        });
    }

    public function approveBlock(string $kind, ReadingService $reading): void
    {
        $this->authorizeReading();
        $block = DiagnosisRowKind::from($kind);

        $this->attempt(function () use ($reading, $block): void {
            $count = $reading->approveBlock($this->main(), $block, $this->admin());
            session()->flash('status', __('admin.reading.block_approved', ['count' => $count]));
        });
    }

    public function startOverride(): void
    {
        $row = $this->selected();
        $this->overriding = true;
        $this->overrideScore = $row->score === null ? ($row->score_state?->value === 'no_trace' ? DiagnosisService::NO_TRACE : '') : number_format($row->score, 1, '.', '');
        $this->overrideReason = (string) $row->override_reason;
    }

    /**
     * "Átírom": the PHI with a reason the leader also reads (20 · 20.2, 28 · 28.2).
     */
    public function saveOverride(ReadingService $reading): void
    {
        $row = $this->selected();

        $this->validate([
            'overrideScore' => ['required', 'string', function (string $attribute, mixed $input, \Closure $fail): void {
                if ($input !== DiagnosisService::NO_TRACE && ! preg_match(self::NUMBER_PATTERN, (string) $input)) {
                    $fail(__('validation.in', ['attribute' => __('admin.diagnoses.score')]));
                }
            }],
            'overrideReason' => ['required', 'string', 'min:3', 'max:1000'],
        ], attributes: ['overrideScore' => __('admin.diagnoses.score'), 'overrideReason' => __('admin.diagnoses.override.reason')]);

        $this->attempt(function () use ($reading, $row): void {
            $reading->overrideRow($row, $this->overrideScore, $this->overrideReason, $this->admin());
            $this->overriding = false;
            $this->selectNext($row);
        });
    }

    /**
     * Areas and the tree: a whole number or "–", no reason (18 · 18.4).
     */
    public function saveHandScore(ReadingService $reading): void
    {
        $row = $this->selected();

        $this->validate(['handScore' => ['required', Rule::in(['1', '2', '3', '4', '5', '6', DiagnosisService::NO_TRACE])]], attributes: ['handScore' => __('admin.diagnoses.score')]);

        $this->attempt(function () use ($reading, $row): void {
            $reading->scoreRow($row, $this->handScore, $this->admin());
            $this->handScore = '';
            $this->selectNext($row);
        });
    }

    public function markInsufficient(ReadingService $reading): void
    {
        $row = $this->selected();

        $this->attempt(fn () => $reading->markInsufficient($row, $this->admin()));
    }

    public function reopen(ReadingService $reading): void
    {
        $row = $this->selected();

        $this->attempt(fn () => $reading->reopenRow($row, $this->admin()));
    }

    public function toggleTrace(int $traceId, ReadingService $reading): void
    {
        $this->attempt(fn () => $reading->toggleTrace($this->traceOfDiagnosis($traceId), $this->admin()));
    }

    public function highlightTrace(int $traceId, ReadingService $reading): void
    {
        $this->attempt(fn () => $reading->highlightTrace($this->traceOfDiagnosis($traceId), $this->admin()));
    }

    /**
     * A trace by hand: the quote, the material, the date and the role (20, question 1).
     */
    public function addTrace(ReadingService $reading): void
    {
        $row = $this->selected();

        $validated = $this->validate([
            'trace.quote' => ['required', 'string', 'min:3', 'max:2000'],
            'trace.material_id' => ['nullable', Rule::in($this->materials()->pluck('id')->map(fn ($id) => (string) $id))],
            'trace.recorded_on' => ['nullable', 'date', 'before_or_equal:today'],
            'trace.role' => ['required', Rule::in(EvidenceTrace::ROLES)],
            'trace.source_type' => ['required', Rule::in(EvidenceTrace::SOURCES)],
        ], attributes: [
            'trace.quote' => __('admin.reading.traces.quote'),
            'trace.material_id' => __('admin.reading.traces.material'),
            'trace.recorded_on' => __('admin.reading.traces.date'),
        ])['trace'];

        $this->attempt(function () use ($reading, $row, $validated): void {
            $reading->addTrace($row, [
                'quote' => $validated['quote'],
                'material_id' => filled($validated['material_id']) ? (int) $validated['material_id'] : null,
                'recorded_on' => filled($validated['recorded_on']) ? $validated['recorded_on'] : null,
                'role' => $validated['role'],
                'source_type' => $validated['source_type'],
            ], $this->admin());
            $this->trace = [...$this->trace, 'quote' => ''];
            session()->flash('status', __('admin.reading.traces.added'));
        });
    }

    /**
     * Areas and the tree take their evidence from the diagnosis' traces (20 · 20.2).
     */
    public function useTrace(int $traceId, ReadingService $reading): void
    {
        $row = $this->selected();

        $this->attempt(fn () => $reading->copyTrace($row, $this->traceOfDiagnosis($traceId), $this->admin()));
    }

    public function newClaim(): void
    {
        $this->authorizeReading();
        $this->editingClaim = 'new';
        $this->claim = ['diagnosis_row_id' => $this->selectedRowId === null ? null : (string) $this->selectedRowId, 'kind' => 'strength', 'text' => '', 'trace_ids' => []];
        $this->resetValidation();
    }

    public function editClaim(int $claimId): void
    {
        $claim = $this->claimOfDiagnosis($claimId);
        $this->editingClaim = (string) $claim->id;
        $this->claim = [
            'diagnosis_row_id' => (string) $claim->diagnosis_row_id,
            'kind' => $claim->kind,
            'text' => $claim->text,
            'trace_ids' => array_map('strval', $claim->trace_ids ?? []),
        ];
        $this->resetValidation();
    }

    public function saveClaim(ReadingService $reading): void
    {
        $this->authorizeReading();

        $this->validate([
            'claim.diagnosis_row_id' => ['required', Rule::in($this->diagnosis->rows()->pluck('id')->map(fn ($id) => (string) $id))],
            'claim.kind' => ['required', Rule::in(DiagnosisClaim::KINDS)],
            'claim.text' => ['required', 'string', 'max:'.DiagnosisText::DEFAULT_MAX_LENGTH],
            'claim.trace_ids' => ['required', 'array', 'min:1'],
        ], ['claim.trace_ids.required' => __('admin.reading.claims.trace_needed'), 'claim.trace_ids.min' => __('admin.reading.claims.trace_needed')], [
            'claim.diagnosis_row_id' => __('admin.reading.claims.row'),
            'claim.text' => __('admin.reading.claims.text'),
        ]);

        $existing = $this->editingClaim === 'new' ? null : $this->claimOfDiagnosis((int) $this->editingClaim);

        $this->attempt(function () use ($reading, $existing): void {
            $reading->saveClaim($this->main(), $existing, [
                'diagnosis_row_id' => (int) $this->claim['diagnosis_row_id'],
                'kind' => $this->claim['kind'],
                'text' => $this->claim['text'],
                'trace_ids' => array_map('intval', $this->claim['trace_ids']),
            ], $this->admin());
            $this->editingClaim = null;
        });
    }

    public function approveClaim(int $claimId, ReadingService $reading): void
    {
        $this->attempt(fn () => $reading->approveClaim($this->claimOfDiagnosis($claimId), $this->admin()));
    }

    public function deleteClaim(int $claimId, ReadingService $reading): void
    {
        $this->attempt(fn () => $reading->deleteClaim($this->claimOfDiagnosis($claimId), $this->admin()));
    }

    public function saveText(string $key, ReadingService $reading): void
    {
        $this->authorizeReading();
        abort_unless(in_array($key, DiagnosisText::KEYS, true), 404);

        $rules = ["texts.{$key}" => ['required', 'string', 'max:'.DiagnosisText::maxLength($key)]];

        if ($key === 'recommendation') {
            $rules['recommendation.row_id'] = ['nullable', Rule::in($this->diagnosis->rows()->pluck('id')->map(fn ($id) => (string) $id))];
            $rules['recommendation.from'] = ['nullable', 'integer', 'between:1,6'];
            $rules['recommendation.to'] = ['nullable', 'integer', 'between:1,6'];
        }

        $this->validate($rules, attributes: ["texts.{$key}" => __('admin.reading.texts.keys.'.$key)]);

        $meta = $key === 'recommendation' && filled($this->recommendation['row_id'])
            ? ['row_id' => (int) $this->recommendation['row_id'], 'from' => filled($this->recommendation['from']) ? (int) $this->recommendation['from'] : null, 'to' => filled($this->recommendation['to']) ? (int) $this->recommendation['to'] : null]
            : null;

        $this->attempt(function () use ($reading, $key, $meta): void {
            $reading->saveText($this->main(), $key, $this->texts[$key], $meta, $this->admin());
            session()->flash('status', __('admin.reading.texts.saved'));
        });
    }

    public function approveText(string $key, ReadingService $reading): void
    {
        $text = DiagnosisText::query()->where('diagnosis_id', $this->diagnosis->id)->where('key', $key)->firstOrFail();

        $this->attempt(fn () => $reading->approveText($text, $this->admin()));
    }

    public function approveGap(ReadingService $reading): void
    {
        $this->authorizeReading();

        $this->attempt(fn () => $reading->approveGap($this->main(), filled($this->gapRowId) ? (int) $this->gapRowId : null, $this->admin()));
    }

    /**
     * The feedback is built in; the next correction marks its chapter
     * "a ti visszajelzésetek alapján" (21 · 21.4, 28 · 28.1). No score changes by it.
     */
    public function incorporateFeedback(ReadingService $reading, InboxService $inbox): void
    {
        $this->authorizeReading();
        $feedback = ReportFeedback::query()->where('main_diagnosis_id', $this->main()->id)->findOrFail((int) $this->feedbackId);

        $feedback->update(['incorporated_at' => now(), 'incorporated_by' => $this->admin()->id]);
        $reading->log($this->diagnosis, $feedback, 'feedback_incorporated', $this->admin(), ['chapter' => $feedback->chapter]);

        InboxItem::query()->open()->where('subject_type', 'report_feedback')->where('subject_id', $feedback->id)->get()
            ->each(fn ($item) => $inbox->close($item, $this->admin()));

        session()->flash('status', __('admin.reading.feedback.incorporated'));
    }

    public function render(ReadingService $reading, ThresholdPolicy $threshold, ProcessOccasionBreakdown $breakdown): View
    {
        $main = $this->main()->refresh();
        $rows = $reading->rows($main);
        $selected = $this->selectedRowId === null ? null : $rows->firstWhere('id', $this->selectedRowId);
        $traces = $selected === null ? collect() : $selected->traces()->with(['material', 'occasion'])->get()->load('material');
        $processDiagnosis = $selected?->kind === DiagnosisRowKind::Process
            ? Diagnosis::query()->where('parent_id', $this->diagnosis->id)->where('level', DiagnosisLevel::Process)->where('process_id', $selected->process_id)->first()
            : null;
        $claims = DiagnosisClaim::query()->where('diagnosis_id', $this->diagnosis->id)->with('row')->orderBy('diagnosis_row_id')->orderBy('id')->get();
        $byHand = $selected !== null && in_array($selected->kind, [DiagnosisRowKind::Area, DiagnosisRowKind::TreeElement], true);

        return view('livewire.admin.main-diagnoses.reading', [
            'main' => $main,
            'blocks' => collect(ReadingService::blocks())->map(fn (DiagnosisRowKind $kind) => ['kind' => $kind, 'rows' => $rows->where('kind', $kind)->values()])->filter(fn (array $block) => $block['rows']->isNotEmpty())->values(),
            'rows' => $rows,
            'selected' => $selected,
            'traces' => $traces,
            'userNames' => User::query()->whereIn('id', $traces->pluck('user_id')->filter())->get()->mapWithKeys(fn ($user) => [$user->id => $user->name]),
            'occasions' => $processDiagnosis === null ? collect() : $breakdown->occasions($processDiagnosis),
            'candidates' => $byHand ? $this->candidateTraces($rows) : collect(),
            'byHand' => $byHand,
            'rowClaims' => $selected === null ? collect() : $claims->where('diagnosis_row_id', $selected->id)->values(),
            'claims' => $claims,
            'claimTraces' => $this->editingClaim !== null && filled($this->claim['diagnosis_row_id'])
                ? EvidenceTrace::query()->where('diagnosis_row_id', (int) $this->claim['diagnosis_row_id'])->get()
                : collect(),
            'textModels' => DiagnosisText::query()->where('diagnosis_id', $this->diagnosis->id)->get()->keyBy('key'),
            'requiredTexts' => $reading->requiredTexts($main),
            'gapRow' => $reading->gapRow($main),
            'gapCandidates' => $rows->filter(fn (DiagnosisRow $row) => in_array($row->kind, [DiagnosisRowKind::Value, DiagnosisRowKind::TreeElement], true))->values(),
            'progress' => $reading->progress($main),
            'showsExperience' => $threshold->showsTeamExperience($main),
            'missingPeers' => $threshold->missingPeers($main),
            'materials' => $this->materials(),
            'events' => ReviewEvent::query()
                ->where('diagnosis_id', $this->diagnosis->id)
                ->with(['actor', 'subject' => fn (MorphTo $subject) => $subject->morphWith([DiagnosisRow::class => ['value', 'process', 'area']])])
                ->latest('id')
                ->limit(50)
                ->get(),
            'isOpen' => in_array($main->stage, [MainDiagnosisStage::Reading, MainDiagnosisStage::Published], true),
            'feedback' => $this->feedbackId === null ? null : ReportFeedback::query()->where('main_diagnosis_id', $main->id)->with('user')->find($this->feedbackId),
        ])->layout('layouts::admin', ['organization' => $this->diagnosis->organization])->title($this->diagnosis->name);
    }

    private function loadTexts(): void
    {
        $texts = DiagnosisText::query()->where('diagnosis_id', $this->diagnosis->id)->get()->keyBy('key');

        foreach (DiagnosisText::KEYS as $key) {
            $this->texts[$key] = (string) $texts->get($key)?->text;
        }

        $meta = $texts->get('recommendation')?->meta ?? [];
        $this->recommendation = [
            'row_id' => isset($meta['row_id']) ? (string) $meta['row_id'] : null,
            'from' => isset($meta['from']) ? (string) $meta['from'] : null,
            'to' => isset($meta['to']) ? (string) $meta['to'] : null,
        ];
    }

    /**
     * @param  Collection<int, DiagnosisRow>  $rows
     * @return Collection<int, EvidenceTrace>
     */
    private function candidateTraces($rows)
    {
        return EvidenceTrace::query()
            ->whereIn('diagnosis_row_id', $rows->whereNotIn('kind', [DiagnosisRowKind::Area, DiagnosisRowKind::TreeElement])->pluck('id'))
            ->whereNull('excluded_at')
            ->when($this->traceFilter['block'] !== '', fn ($query) => $query->whereIn('diagnosis_row_id', $rows->where('kind.value', $this->traceFilter['block'])->pluck('id')))
            ->when($this->traceFilter['source'] !== '', fn ($query) => $query->where('source_type', $this->traceFilter['source']))
            ->when(trim($this->traceFilter['word']) !== '', fn ($query) => $query->whereLike('quote', '%'.trim($this->traceFilter['word']).'%'))
            ->with('row.value', 'row.process')
            ->latest('recorded_on')
            ->limit(30)
            ->get()
            ->unique('quote')
            ->values();
    }

    /**
     * @return Collection<int, Material>
     */
    private function materials()
    {
        return Material::query()->where('parent_diagnosis_id', $this->diagnosis->id)->latest('id')->get(['id', 'uuid', 'original_name', 'source', 'is_situation', 'created_at']);
    }

    private function selectNext(DiagnosisRow $row): void
    {
        $rows = app(ReadingService::class)->rows($this->main());
        $after = $rows->skipUntil(fn (DiagnosisRow $candidate) => $candidate->id === $row->id)->skip(1);
        $next = $after->first(fn (DiagnosisRow $candidate) => $candidate->approved_at === null) ?? $rows->first(fn (DiagnosisRow $candidate) => $candidate->approved_at === null);

        $this->selectedRowId = $next?->id ?? $row->id;
        $this->reset(['overriding', 'overrideScore', 'overrideReason', 'handScore']);
    }

    private function main(): MainDiagnosis
    {
        return $this->diagnosis->main;
    }

    private function admin(): User
    {
        return auth('admin')->user();
    }

    private function authorizeReading(): void
    {
        Gate::authorize('update', $this->diagnosis);
    }

    private function row(int $rowId): DiagnosisRow
    {
        return $this->diagnosis->rows()->findOrFail($rowId);
    }

    private function selected(): DiagnosisRow
    {
        $this->authorizeReading();
        abort_if($this->selectedRowId === null, 404);

        return $this->row($this->selectedRowId);
    }

    private function traceOfDiagnosis(int $traceId): EvidenceTrace
    {
        $this->authorizeReading();

        return EvidenceTrace::query()->whereIn('diagnosis_row_id', $this->diagnosis->rows()->select('id'))->findOrFail($traceId);
    }

    private function claimOfDiagnosis(int $claimId): DiagnosisClaim
    {
        $this->authorizeReading();

        return DiagnosisClaim::query()->where('diagnosis_id', $this->diagnosis->id)->findOrFail($claimId);
    }

    /**
     * Runs a step and shows a broken rule as an error message.
     */
    private function attempt(callable $change): void
    {
        try {
            $change();
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }
}
