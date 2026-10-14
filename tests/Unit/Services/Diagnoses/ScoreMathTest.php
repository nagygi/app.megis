<?php

use App\Services\Diagnoses\ScoreMath;

it('averages and rounds half up to one decimal', function () {
    expect(ScoreMath::mean([4, 5]))->toBe(4.5)
        ->and(ScoreMath::round1(4.25))->toBe(4.3)
        ->and(ScoreMath::round1(ScoreMath::mean([4, 4, 5])))->toBe(4.3)
        ->and(ScoreMath::mean([]))->toBeNull();
});

it('calculates the deviation of the occasions', function () {
    expect(round(ScoreMath::deviation([2, 4, 4, 4, 5, 5, 7, 9]), 2))->toBe(2.0)
        ->and(ScoreMath::deviation([3]))->toBe(0.0);
});

it('calculates the ring, the step and the points from the unrounded values', function () {
    // 19 measuring points of the example (18 · 18.1), every one 4,8: the optimum, 80%.
    $scores = array_fill(0, 19, 4.8);

    expect(ScoreMath::ringPercent($scores))->toBe(80)
        ->and(ScoreMath::step($scores))->toBe(4)
        ->and(ScoreMath::points($scores))->toBe(['points' => 91, 'max' => 114]);
});

it('leaves "–" rows out of the ring: fewer measuring points', function () {
    expect(ScoreMath::ringPercent([6.0, 3.0]))->toBe(75)
        ->and(ScoreMath::ringPercent([]))->toBeNull()
        ->and(ScoreMath::step([5.99, 6.0]))->toBe(5);
});

it('counts only 1–6 answers, not "nem tudom"', function () {
    expect(ScoreMath::numericAnswers(['4', 'dont_know', '6', null, '7']))->toBe([4.0, 6.0]);
});
