<?php

use App\Services\Versioning\ListDiff;

it('marks kept, added and removed items', function () {
    $diff = ListDiff::compare(['Kérdezek', 'Meghallgatom'], ['Kérdezek', 'Visszakérdezek']);

    expect($diff['old'])->toBe([
        ['text' => 'Kérdezek', 'status' => ListDiff::UNCHANGED],
        ['text' => 'Meghallgatom', 'status' => ListDiff::REMOVED],
    ])->and($diff['new'])->toBe([
        ['text' => 'Kérdezek', 'status' => ListDiff::UNCHANGED],
        ['text' => 'Visszakérdezek', 'status' => ListDiff::ADDED],
    ]);
});

it('marks an item that changed place as moved on both sides', function () {
    $diff = ListDiff::compare(['A', 'B', 'C'], ['C', 'A', 'B']);

    expect(array_column($diff['old'], 'status'))->toBe([ListDiff::UNCHANGED, ListDiff::UNCHANGED, ListDiff::MOVED])
        ->and(array_column($diff['new'], 'status'))->toBe([ListDiff::MOVED, ListDiff::UNCHANGED, ListDiff::UNCHANGED]);
});

it('matches repeated items one by one', function () {
    $diff = ListDiff::compare(['A', 'A'], ['A']);

    expect(array_column($diff['old'], 'status'))->toBe([ListDiff::UNCHANGED, ListDiff::REMOVED]);
});

it('handles empty lists', function () {
    expect(ListDiff::compare([], ['A'])['new'])->toBe([['text' => 'A', 'status' => ListDiff::ADDED]])
        ->and(ListDiff::compare(['A'], [])['old'])->toBe([['text' => 'A', 'status' => ListDiff::REMOVED]]);
});
