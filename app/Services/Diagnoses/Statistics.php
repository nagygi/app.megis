<?php

namespace App\Services\Diagnoses;

/**
 * Average and spread of the users' answers (04 · 4.5): arithmetic mean
 * rounded to one decimal, population standard deviation.
 */
class Statistics
{
    /**
     * @param  list<float|int>  $numbers
     * @return array{n: int, mean: ?float, deviation: ?float}
     */
    public static function summarize(array $numbers): array
    {
        $count = count($numbers);

        if ($count === 0) {
            return ['n' => 0, 'mean' => null, 'deviation' => null];
        }

        $mean = array_sum($numbers) / $count;
        $variance = array_sum(array_map(fn (float|int $number): float => ($number - $mean) ** 2, $numbers)) / $count;

        return [
            'n' => $count,
            'mean' => round($mean, 1),
            'deviation' => round(sqrt($variance), 2),
        ];
    }
}
