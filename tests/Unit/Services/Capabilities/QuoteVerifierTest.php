<?php

use App\Services\Capabilities\CaseLabelScrubber;
use App\Services\Capabilities\QuoteVerifier;

it('finds a quote word for word, without minding spacing, case and quotation marks (6.1)', function () {
    $source = "Beszélő 1: A heti alkalom fél éve fut,\n és minden héten döntünk a „következő” lépésről.";

    expect((new QuoteVerifier)->occursIn('a heti alkalom fél éve fut, és minden héten döntünk a következő lépésről', $source))->toBeTrue()
        ->and((new QuoteVerifier)->occursIn('A heti alkalom egy éve fut', $source))->toBeFalse()
        ->and((new QuoteVerifier)->occursIn('', $source))->toBeFalse();
});

it('reads "[...]" as a cut part and "[név]" as a removed name, in order', function () {
    $source = 'Kata szerint a heti alkalom fél éve fut, és Péter minden héten dönt.';

    expect((new QuoteVerifier)->occursIn('[név] szerint a heti alkalom [...] minden héten dönt', $source))->toBeTrue()
        ->and((new QuoteVerifier)->occursIn('minden héten dönt [...] a heti alkalom', $source))->toBeFalse();
});

it('takes the case names and the codes of the tár out of a text for the AI (NM-12)', function () {
    $scrubber = new CaseLabelScrubber(['DXC', 'SZV']);

    expect($scrubber->scrub('A DXC-nél is látszott (DXCK-23), az SZV-nél nem; lásd RC-46 és M-002, T-0001.'))
        ->toBe('A -nél is látszott, az -nél nem; lásd és,.')
        ->and($scrubber->scrub('Az ÚJ-01 és az SZVK-08 forrás'))->toBe('Az és az forrás')
        ->and($scrubber->scrub('Szervezeti DXCélok'))->toBe('Szervezeti DXCélok');
});
