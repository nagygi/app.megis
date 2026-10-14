<?php

use App\Services\Materials\TranscriptParser;

it('keeps the speaker labels of a transcript', function () {
    $segments = (new TranscriptParser)->parse("Anna K.: Csúszunk a szállítással.\nPéter: Szóljunk most.\nfolytatja a gondolatot\nAnna K.: Rendben.");

    expect($segments)->toBe([
        ['speaker' => 'Anna K.', 'text' => 'Csúszunk a szállítással.'],
        ['speaker' => 'Péter', 'text' => "Szóljunk most.\nfolytatja a gondolatot"],
        ['speaker' => 'Anna K.', 'text' => 'Rendben.'],
    ]);
});

it('reads labels after a time stamp', function () {
    $segments = (new TranscriptParser)->parse("[00:01:12] Beszélő 1: Kezdjük.\n[00:01:20] Beszélő 2: Jó.");

    expect(array_column($segments, 'speaker'))->toBe(['Beszélő 1', 'Beszélő 2']);
});

it('treats a text without labels as one speaker', function () {
    $segments = (new TranscriptParser)->parse("Múlt héten csúszott a szállítás.\nSzóltam az ügyfélnek.");

    expect($segments)->toBe([['speaker' => 'Beszélő 1', 'text' => "Múlt héten csúszott a szállítás.\nSzóltam az ügyfélnek."]]);
});
