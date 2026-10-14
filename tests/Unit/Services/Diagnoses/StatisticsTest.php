<?php

use App\Services\Diagnoses\Statistics;

it('rounds the mean to one decimal', function () {
    expect(Statistics::summarize([4, 5, 5]))->toMatchArray(['n' => 3, 'mean' => 4.7]);
});

it('gives the population standard deviation', function () {
    expect(Statistics::summarize([1, 6])['deviation'])->toBe(2.5)
        ->and(Statistics::summarize([4, 4, 4])['deviation'])->toBe(0.0);
});

it('has no mean without answers', function () {
    expect(Statistics::summarize([]))->toBe(['n' => 0, 'mean' => null, 'deviation' => null]);
});
