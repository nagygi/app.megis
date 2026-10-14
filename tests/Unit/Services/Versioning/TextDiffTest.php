<?php

use App\Services\Versioning\TextDiff;

it('marks changed words and keeps the rest', function () {
    expect(TextDiff::compare('Kérdezek, mielőtt ítélek.', 'Kérdezek, mielőtt döntök.'))->toBe([
        ['type' => TextDiff::EQUAL, 'text' => 'Kérdezek, mielőtt '],
        ['type' => TextDiff::DELETE, 'text' => 'ítélek.'],
        ['type' => TextDiff::INSERT, 'text' => 'döntök.'],
    ]);
});

it('gives back both texts from the segments', function () {
    $old = 'A kérdés az első mondat.';
    $new = 'A jó kérdés az első és az utolsó mondat.';
    $segments = TextDiff::compare($old, $new);

    $rebuild = fn (string $skipped): string => implode('', array_column(
        array_filter($segments, fn (array $segment): bool => $segment['type'] !== $skipped),
        'text',
    ));

    expect($rebuild(TextDiff::INSERT))->toBe($old)
        ->and($rebuild(TextDiff::DELETE))->toBe($new);
});

it('returns nothing for two empty texts', function () {
    expect(TextDiff::compare('', ''))->toBe([]);
});
