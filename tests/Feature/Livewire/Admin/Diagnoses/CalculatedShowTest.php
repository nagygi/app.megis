<?php

use App\Enums\MainDiagnosisStage;
use App\Jobs\RecalculateMainDiagnosis;
use App\Livewire\Admin\Diagnoses\Show;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\MainDiagnoses\StageMachine;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(fn () => Queue::fake());

it('shows the occasions of a process diagnosis with their points (13 · 13.5)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    $material = scoredMaterial($main, occasionOn($process, 5), [0 => 4, 1 => 2, 2 => 3], $setup['leader']);
    scoredMaterial($main, occasionOn($process, 1), [0 => 6, 1 => 2, 2 => 3]);
    app(DiagnosisCalculator::class)->recalculate($main);
    actingAsAdmin($setup['admin']);

    Livewire::test(Show::class, ['diagnosis' => processDiagnosisOf($main, $process)])
        ->assertSee('A folyamat pontja · Retró')
        ->assertSee('3,3')
        ->assertSee(now()->subDays(5)->format('Y.m.d.'))
        ->assertSee($setup['leader']->name)
        ->assertSeeHtml(route('admin.materials.show', $material))
        ->assertSee('2 felhasznált alkalom')
        ->assertSee('Legalább 2 feldolgozott alkalom kell')
        ->assertDontSee('A márkád alapja');
});

it('writes a calculated row over only with a reason (13 · 13.5)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    scoredMaterial($main, occasionOn($process, 5), [0 => 4, 1 => 2, 2 => 3]);
    scoredMaterial($main, occasionOn($process, 1), [0 => 4, 1 => 2, 2 => 3]);
    app(DiagnosisCalculator::class)->recalculate($main);
    $diagnosis = processDiagnosisOf($main, $process);
    $row = $diagnosis->valueRows()->orderBy('position')->first();
    actingAsAdmin($setup['admin']);

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('editRow', $row->id)
        ->set('rowScore', '5')
        ->call('saveRow')
        ->assertHasErrors(['rowOverrideReason'])
        ->set('rowOverrideReason', 'A jegyzetekből hiányzik a döntés.')
        ->call('saveRow')
        ->assertHasNoErrors()
        ->assertSee('kézzel átírva')
        ->assertSee('automatikusan: 4,0')
        ->call('resetRow', $row->id)
        ->assertDontSee('kézzel átírva');

    expect($row->refresh()->score)->toBe(4.0);
    Queue::assertPushed(RecalculateMainDiagnosis::class);
});

it('writes the point of a process over with a reason and back', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    scoredMaterial($main, occasionOn($process, 5), [0 => 4, 1 => 2, 2 => 3]);
    scoredMaterial($main, occasionOn($process, 1), [0 => 4, 1 => 2, 2 => 3]);
    app(DiagnosisCalculator::class)->recalculate($main);
    $diagnosis = processDiagnosisOf($main, $process);
    actingAsAdmin($setup['admin']);

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('editScore')
        ->set('scoreInput', '4.5')
        ->call('saveScore')
        ->assertHasErrors(['scoreReason'])
        ->set('scoreInput', '7')
        ->set('scoreReason', 'Workshop alapján.')
        ->call('saveScore')
        ->assertHasErrors(['scoreInput'])
        ->set('scoreInput', '4.5')
        ->call('saveScore')
        ->assertHasNoErrors();

    $diagnosis->refresh();
    expect($diagnosis->score)->toBe(4.5)
        ->and($diagnosis->automatic_score)->toBe(3.0)
        ->and($diagnosis->score_overridden_by)->toBe($setup['admin']->id);

    app(DiagnosisCalculator::class)->recalculate($main);

    expect($diagnosis->refresh()->score)->toBe(4.5)
        ->and($main->diagnosis->rows()->where('process_id', $process->id)->sole()->score)->toBe(4.5);

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])->call('resetScore');

    expect($diagnosis->refresh()->score)->toBe(3.0);
});

it('shows the calculation and the process and area blocks of the main diagnosis (18)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    scoredMaterial($main, null, [0 => 4, 1 => 5, 2 => 3]);
    app(DiagnosisCalculator::class)->recalculate($main);
    actingAsAdmin($setup['admin']);

    Livewire::test(Show::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('Belső működés')
        ->assertSee('67%')
        ->assertSee('12 / 18')
        ->assertSee('Folyamatok')
        ->assertSee('Retró folyamat-diagnózisa')
        ->assertSee('Területek')
        ->assertSee('Kultúra és közösség')
        ->assertDontSee('+ Érték');
});

it('counts the occasions added after publishing (13 · 13.4)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    scoredMaterial($main, occasionOn($process, 5), [0 => 4, 1 => 2, 2 => 3]);
    scoredMaterial($main, occasionOn($process, 4), [0 => 4, 1 => 2, 2 => 3]);
    app(DiagnosisCalculator::class)->recalculate($main);
    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);
    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Published, $setup['admin']);
    $this->travel(1)->hour();
    scoredMaterial($main, occasionOn($process, 0), [0 => 6, 1 => 6, 2 => 6]);
    actingAsAdmin($setup['admin']);

    Livewire::test(Show::class, ['diagnosis' => processDiagnosisOf($main, $process)])
        ->assertSee('1 új alkalom a publikálás óta');
});
