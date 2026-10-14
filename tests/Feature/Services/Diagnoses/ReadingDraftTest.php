<?php

use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Jobs\DraftReading;
use App\Models\DiagnosisClaim;
use App\Models\DiagnosisText;
use App\Models\EvidenceTrace;
use App\Models\InboxItem;
use App\Models\MainDiagnosis;
use App\Services\Ai\AiGateway;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\Diagnoses\ReadingDraftService;
use App\Services\MainDiagnoses\StageMachine;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;

function structuredAnswer(array $data): StructuredTextResponse
{
    return new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));
}

/**
 * A running diagnosis with one material whose first value has a quote.
 *
 * @return array{0: array<string, mixed>, 1: MainDiagnosis, 2: EvidenceTrace}
 */
function diagnosisWithTrace(): array
{
    Queue::fake();
    [$setup, $main] = calculatedDiagnosis();
    $material = scoredMaterial($main, null, [0 => 4, 1 => null, 2 => null], $setup['leader']);
    $detection = $material->detections()->create(['quote' => 'Szóltam a csúszásról, mielőtt kérdezték volna.', 'free_name' => 'Őszinteség', 'speaker' => null, 'strength' => 3]);
    $material->scores()->where('value_id', $main->valueSetVersion->snapshot['values'][0]['value_id'])->update(['detection_ids' => json_encode([$detection->id])]);
    app(DiagnosisCalculator::class)->recalculate($main);

    return [$setup, $main->refresh(), EvidenceTrace::query()->sole()];
}

function textsAnswer(?string $rowRef = 'R1'): StructuredTextResponse
{
    return structuredAnswer([
        'summary_values' => 'Az értékeitek a tettekben is látszanak.',
        'summary_processes' => 'A folyamatokról még kevés a nyom.',
        'summary_areas' => 'A területeket a mégis pontozza.',
        'summary_brand' => 'A fa gyökere erős.',
        'brand_reading' => 'A gyökér és a törzs összhangban van.',
        'recommendation' => ['text' => 'A retrón kezdenénk.', 'row_ref' => $rowRef, 'from' => 2, 'to' => 3],
    ]);
}

it('drafts claims on the rows from their own traces, never with names (18 · 18.3)', function () {
    [$setup, $main, $trace] = diagnosisWithTrace();
    $prompts = [];
    AiGateway::fake(function (string $prompt) use (&$prompts, $trace) {
        $prompts[] = $prompt;

        return count($prompts) === 1
            ? structuredAnswer(['claims' => [
                ['row_ref' => 'R1', 'kind' => 'strength', 'text' => 'Nálatok szólnak a csúszásról.', 'trace_refs' => ['T'.$trace->id]],
                // No trace of its own row: dropped.
                ['row_ref' => 'R2', 'kind' => 'hunch', 'text' => 'Talán.', 'trace_refs' => ['T'.$trace->id]],
                ['row_ref' => 'R9', 'kind' => 'crack', 'text' => 'Nincs ilyen sor.', 'trace_refs' => []],
            ]])
            : textsAnswer();
    });

    app(ReadingDraftService::class)->draft($main);

    $claim = DiagnosisClaim::query()->sole();
    expect($claim->diagnosis_row_id)->toBe($trace->diagnosis_row_id)
        ->and($claim->kind)->toBe('strength')
        ->and($claim->status)->toBe('draft')
        ->and($claim->trace_ids)->toBe([$trace->id])
        ->and($claim->ai_call_id)->not->toBeNull()
        ->and(implode("\n", $prompts))->toContain('Szóltam a csúszásról')
        ->not->toContain($setup['leader']->name)
        ->not->toContain($setup['leader']->email);

    $recommendation = DiagnosisText::query()->where('key', 'recommendation')->sole();
    expect(DiagnosisText::query()->count())->toBe(6)
        ->and($recommendation->meta)->toBe(['row_id' => $main->diagnosis->rows()->orderBy('position')->first()->id, 'from' => 2, 'to' => 3])
        ->and($main->refresh()->calculation['drafts']['status'])->toBe('ready');
});

it('keeps what the admin already worked on when drafting again', function () {
    [, $main, $trace] = diagnosisWithTrace();
    $approved = DiagnosisClaim::create(['diagnosis_id' => $main->diagnosis_id, 'diagnosis_row_id' => $trace->diagnosis_row_id, 'kind' => 'crack', 'text' => 'Jóváhagyott.', 'status' => 'approved', 'trace_ids' => [$trace->id]]);
    DiagnosisText::create(['diagnosis_id' => $main->diagnosis_id, 'key' => 'summary_values', 'text' => 'A mégis mondata.', 'status' => 'approved']);
    AiGateway::fake([
        structuredAnswer(['claims' => [['row_ref' => 'R1', 'kind' => 'strength', 'text' => 'Új vázlat.', 'trace_refs' => ['T'.$trace->id]]]]),
        textsAnswer(null),
    ]);

    app(ReadingDraftService::class)->draft($main);

    expect($approved->refresh()->text)->toBe('Jóváhagyott.')
        ->and(DiagnosisClaim::query()->count())->toBe(2)
        ->and(DiagnosisText::query()->where('key', 'summary_values')->value('text'))->toBe('A mégis mondata.')
        ->and(DiagnosisText::query()->where('key', 'recommendation')->value('meta'))->toBeNull();
});

it('starts the drafts when reading starts', function () {
    [$setup, $main] = diagnosisWithTrace();

    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);

    Queue::assertPushed(DraftReading::class, fn (DraftReading $job) => $job->main->is($main));
});

it('opens a Beérkező row when the drafts fail for good', function () {
    [, $main] = diagnosisWithTrace();

    (new DraftReading($main))->failed(new RuntimeException('down'));

    expect($main->refresh()->calculation['drafts']['status'])->toBe('failed')
        ->and(InboxItem::query()->sole()->type)->toBe(InboxItemType::ProcessingError);
});
