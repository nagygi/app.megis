<?php

use App\Enums\DiagnosisRowKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\ScoreState;
use App\Livewire\Admin\MainDiagnoses\Reading;
use App\Models\Diagnosis;
use App\Models\DiagnosisClaim;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Models\ReviewEvent;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\Diagnoses\ReadingService;
use App\Services\MainDiagnoses\StageMachine;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(fn () => Queue::fake());

/**
 * A diagnosis in reading: one material with a quote on the first value, two
 * occasions of the process.
 *
 * @return array{0: array<string, mixed>, 1: MainDiagnosis}
 */
function diagnosisInReading(): array
{
    [$setup, $main, $process] = calculatedDiagnosis();
    $material = scoredMaterial($main, null, [0 => 4, 1 => 3, 2 => null], $setup['leader']);
    $detection = $material->detections()->create(['quote' => 'Szóltam a csúszásról, mielőtt kérdezték volna.', 'free_name' => 'Őszinteség', 'strength' => 3]);
    $material->scores()->where('value_id', $main->valueSetVersion->snapshot['values'][0]['value_id'])->update(['detection_ids' => json_encode([$detection->id])]);
    scoredMaterial($main, occasionOn($process, 5), [0 => 3, 1 => 3, 2 => 3]);
    scoredMaterial($main, occasionOn($process, 2), [0 => 5, 1 => 5, 2 => 5]);
    app(DiagnosisCalculator::class)->recalculate($main);
    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Reading, $setup['admin']);
    actingAsAdmin($setup['admin']);

    return [$setup, $main->refresh()];
}

function rowOf(MainDiagnosis $main, DiagnosisRowKind $kind, int $index = 0): DiagnosisRow
{
    return $main->diagnosis->rows()->where('kind', $kind)->orderBy('position')->with('diagnosis')->get()[$index];
}

it('lists the measuring points in the order of the report with the counter (20 · 20.1)', function () {
    [, $main] = diagnosisInReading();

    Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->assertSeeInOrder(['Értékek', 'Folyamatok', 'Területek', 'A márkád alapja'])
        ->assertSee('0 / 13 jóváhagyva')
        ->assertSet('selectedRowId', rowOf($main, DiagnosisRowKind::Value)->id)
        ->assertSee('Szóltam a csúszásról')
        ->assertSee('Amit a vezető olvas');
});

it('approves a row and steps to the next waiting one, logging it (20 · 20.2)', function () {
    [$setup, $main] = diagnosisInReading();
    $first = rowOf($main, DiagnosisRowKind::Value);

    Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->call('approve')
        ->assertSet('selectedRowId', rowOf($main, DiagnosisRowKind::Value, 1)->id)
        ->assertSee('1 / 13 jóváhagyva');

    expect($first->refresh()->approved_by)->toBe($setup['admin']->id)
        ->and(ReviewEvent::query()->where('action', 'approved')->sole()->subject_id)->toBe($first->id);
});

it('approves a block but leaves the rows written over (20 · 20.2)', function () {
    [, $main] = diagnosisInReading();
    $overridden = rowOf($main, DiagnosisRowKind::Value, 1);
    $overridden->forceFill(['overridden_at' => now(), 'override_reason' => 'kézzel'])->save();

    Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->call('approveBlock', 'value')
        ->assertSee('2 sor jóváhagyva.');

    expect($overridden->refresh()->approved_at)->toBeNull()
        ->and($main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->whereNotNull('approved_at')->count())->toBe(2);
});

it('writes a row over only with a reason, and a process row moves its process diagnosis (20 · 20.2)', function () {
    [, $main] = diagnosisInReading();
    $processRow = rowOf($main, DiagnosisRowKind::Process);

    Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->call('selectRow', $processRow->id)
        ->call('startOverride')
        ->set('overrideScore', '2')
        ->call('saveOverride')
        ->assertHasErrors(['overrideReason'])
        ->set('overrideReason', 'A retrón a döntés mindig elmarad.')
        ->call('saveOverride')
        ->assertHasNoErrors();

    $processRow->refresh();
    $processDiagnosis = Diagnosis::query()->where('parent_id', $main->diagnosis_id)->where('process_id', $processRow->process_id)->sole();

    expect($processRow->score)->toBe(2.0)
        ->and($processRow->reviewState())->toBe('overridden')
        ->and($processRow->override_reason)->toBe('A retrón a döntés mindig elmarad.')
        ->and($processDiagnosis->score)->toBe(2.0)
        ->and(ReviewEvent::query()->where('action', 'overridden')->sole()->payload['reason'])->toBe('A retrón a döntés mindig elmarad.');
});

it('scores an area by hand with a whole number and picks its evidence (20 · 20.2)', function () {
    [$setup, $main] = diagnosisInReading();
    $area = rowOf($main, DiagnosisRowKind::Area);
    $source = EvidenceTrace::query()->firstOrFail();

    Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->call('selectRow', $area->id)
        ->assertDontSee('Átírom')
        ->set('handScore', '4.5')
        ->call('saveHandScore')
        ->assertHasErrors(['handScore'])
        ->set('handScore', '4')
        ->call('saveHandScore')
        ->assertHasNoErrors()
        ->call('selectRow', $area->id)
        ->set('traceFilter.word', 'csúszás')
        ->assertSee('Szóltam a csúszásról')
        ->call('useTrace', $source->id);

    $area->refresh();
    expect($area->score)->toBe(4.0)
        ->and($area->approved_at)->not->toBeNull()
        ->and($area->traces()->sole()->created_by)->toBe($setup['admin']->id);
});

it('takes a trace out, highlights one and adds one by hand (20 · 20.2)', function () {
    [, $main] = diagnosisInReading();
    $trace = EvidenceTrace::query()->firstOrFail();

    $component = Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->call('toggleTrace', $trace->id);

    expect($trace->refresh()->excluded_at)->not->toBeNull()
        ->and($trace->raw_detection_id)->not->toBeNull();

    $component->call('highlightTrace', $trace->id);
    expect($trace->refresh()->highlighted_at)->not->toBeNull()
        ->and($trace->excluded_at)->toBeNull();

    $component->set('trace.quote', '')
        ->call('addTrace')
        ->assertHasErrors(['trace.quote'])
        ->set('trace.quote', 'A heti jegyzetben a „mi nem sikerült” pont üres.')
        ->set('trace.source_type', 'note')
        ->call('addTrace')
        ->assertHasNoErrors();

    // The calculation keeps what was chosen by hand.
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    expect(rowOf($main, DiagnosisRowKind::Value)->traces()->count())->toBe(2)
        ->and($trace->refresh()->highlighted_at)->not->toBeNull();
});

it('keeps a claim to the traces of its own row and approves it (20 · 20.3)', function () {
    [, $main] = diagnosisInReading();
    $row = rowOf($main, DiagnosisRowKind::Value);
    $trace = EvidenceTrace::query()->firstOrFail();

    $component = Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->call('newClaim')
        ->set('claim.text', 'Nálatok szólnak a csúszásról, mielőtt baj lenne.')
        ->call('saveClaim')
        ->assertHasErrors(['claim.trace_ids'])
        ->set('claim.trace_ids', [(string) $trace->id])
        ->set('claim.text', str_repeat('a', 161))
        ->call('saveClaim')
        ->assertHasErrors(['claim.text'])
        ->set('claim.text', 'Nálatok szólnak a csúszásról, mielőtt baj lenne.')
        ->call('saveClaim')
        ->assertHasNoErrors();

    $claim = DiagnosisClaim::query()->sole();
    expect($claim->diagnosis_row_id)->toBe($row->id)->and($claim->status)->toBe('draft');

    $component->call('approveClaim', $claim->id);
    expect($claim->refresh()->isApproved())->toBeTrue()->and($claim->approved_at)->not->toBeNull();

    $component->call('deleteClaim', $claim->id);
    expect(DiagnosisClaim::query()->count())->toBe(0);
});

it('saves and approves the texts of the reading within their length (20 · 20.4)', function () {
    [, $main] = diagnosisInReading();

    $component = Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->set('texts.brand_reading', str_repeat('a', 601))
        ->call('saveText', 'brand_reading')
        ->assertHasErrors(['texts.brand_reading'])
        ->set('texts.brand_reading', 'A gyökér erős, a korona még keresi magát.')
        ->call('saveText', 'brand_reading')
        ->assertHasNoErrors()
        ->call('approveText', 'brand_reading');

    expect(DiagnosisText::query()->where('key', 'brand_reading')->sole()->isApproved())->toBeTrue();

    $component->set('texts.recommendation', 'A retrón kezdenénk.')
        ->set('recommendation.row_id', (string) rowOf($main, DiagnosisRowKind::Process)->id)
        ->set('recommendation.from', '2')
        ->set('recommendation.to', '3')
        ->call('saveText', 'recommendation');

    expect(DiagnosisText::query()->where('key', 'recommendation')->sole()->meta)->toBe(['row_id' => rowOf($main, DiagnosisRowKind::Process)->id, 'from' => 2, 'to' => 3]);
});

it('opens the Publikálás tab only when everything is approved (20 · 20.5)', function () {
    [$setup, $main] = diagnosisInReading();
    $reading = app(ReadingService::class);
    $admin = $setup['admin'];

    expect($reading->progress($main)['complete'])->toBeFalse();

    foreach ($main->diagnosis->rows()->with('diagnosis')->get() as $row) {
        in_array($row->kind, [DiagnosisRowKind::Area, DiagnosisRowKind::TreeElement], true)
            ? $reading->scoreRow($row, '3', $admin)
            : $reading->approveRow($row, $admin);
    }

    foreach ($reading->requiredTexts($main) as $key) {
        $reading->approveText($reading->saveText($main, $key, 'Szöveg.', null, $admin), $admin);
    }

    $reading->approveGap($main->refresh(), null, $admin);

    expect($reading->progress($main->refresh())['complete'])->toBeTrue();
});

it('lets an approved row fall back when its calculated value changes (20, question 5)', function () {
    [$setup, $main] = diagnosisInReading();
    $row = rowOf($main, DiagnosisRowKind::Value);
    app(ReadingService::class)->approveRow($row, $setup['admin']);

    scoredMaterial($main, null, [0 => 1, 1 => 3, 2 => null]);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    expect($row->refresh()->approved_at)->toBeNull()
        ->and(ReviewEvent::query()->where('action', 'fell_back')->sole()->subject_id)->toBe($row->id);
});

it('keeps the reading closed before it starts', function () {
    [$setup, $main] = runningDiagnosis();
    app(DiagnosisCalculator::class)->recalculate($main);
    actingAsAdmin($setup['admin']);
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->first();
    $row->update(['score_state' => ScoreState::Scored, 'score' => 3]);

    Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('Az olvasat az „Olvasat indítása” után nyílik meg.')
        ->assertDontSee('Jóváhagyom');
});
