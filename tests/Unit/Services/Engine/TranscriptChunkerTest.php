<?php

use App\Services\Engine\TranscriptChunker;

it('keeps a short transcript in one part', function () {
    expect((new TranscriptChunker)->chunks("Anna: Szia.\nPéter: Helló.", 100))->toBe(["Anna: Szia.\nPéter: Helló."]);
});

it('splits at line breaks without going over the limit', function () {
    $lines = ['Anna: '.str_repeat('a', 40), 'Péter: '.str_repeat('b', 40), 'Anna: '.str_repeat('c', 40)];

    $chunks = (new TranscriptChunker)->chunks(implode("\n", $lines), 100);

    expect($chunks)->toBe([$lines[0]."\n".$lines[1], $lines[2]])
        ->and(max(array_map('mb_strlen', $chunks)))->toBeLessThanOrEqual(100);
});

it('splits an overlong line at sentence ends', function () {
    $chunks = (new TranscriptChunker)->chunks('Első mondat itt. Második mondat itt. Harmadik mondat itt.', 40);

    expect($chunks)->toBe(['Első mondat itt.'."\n".'Második mondat itt.', 'Harmadik mondat itt.']);
});

it('returns nothing for an empty text', function () {
    expect((new TranscriptChunker)->chunks("  \n ", 100))->toBe([]);
});
