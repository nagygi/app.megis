<?php

use App\Livewire\Admin\SupportQuestions\Index;
use App\Livewire\Mirror\Record;
use App\Models\SupportQuestion;
use Livewire\Livewire;

it('starts with the four example questions in order', function () {
    expect(SupportQuestion::query()->forRecording()->pluck('text')->all())->toBe([
        'Ki volt még ott?',
        'Mit éreztél abban a pillanatban?',
        'Mit tettél végül?',
        'Miért volt ez fontos neked?',
    ]);
});

it('adds a question at the end and requires the text', function () {
    actingAsAdmin();

    Livewire::test(Index::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors(['text' => 'required'])
        ->set('text', 'Ki döntött végül?')
        ->call('save')
        ->assertHasNoErrors();

    expect(SupportQuestion::query()->forRecording()->pluck('text')->last())->toBe('Ki döntött végül?');
});

it('orders by dragging and hides inactive questions from the recorder', function () {
    actingAsAdmin();
    $last = SupportQuestion::query()->forRecording()->get()->last();

    Livewire::test(Index::class)
        ->call('sortQuestion', $last->id, 0)
        ->call('toggleActive', $last->id);

    expect(SupportQuestion::query()->orderBy('position')->first()->id)->toBe($last->id);

    Livewire::test(Record::class)->assertDontSee($last->text);
});
