<?php

namespace App\Services\Diagnoses;

/**
 * The arithmetic of the diagnosis (13 · 13.3, 18 · 18.4), without the framework.
 * Rounding: rows and summaries to one decimal, the % to a whole number,
 * always from the unrounded values.
 */
final class ScoreMath
{
    /**
     * @param  list<int|float>  $numbers
     */
    public static function mean(array $numbers): ?float
    {
        return $numbers === [] ? null : array_sum($numbers) / count($numbers);
    }

    /**
     * Population standard deviation, as on the aggregated rows (04 · 4.5).
     *
     * @param  list<int|float>  $numbers
     */
    public static function deviation(array $numbers): ?float
    {
        $mean = self::mean($numbers);

        if ($mean === null) {
            return null;
        }

        return sqrt(array_sum(array_map(fn ($number) => ($number - $mean) ** 2, $numbers)) / count($numbers));
    }

    /**
     * One decimal, half up ("4,25" → "4,3").
     */
    public static function round1(?float $number): ?float
    {
        return $number === null ? null : round($number + 1e-9, 1);
    }

    /**
     * The ring: the sum of the PHIs over six times the measuring points,
     * "–" rows left out, a whole number (18 · 18.4).
     *
     * @param  list<float>  $scores  the PHIs of the measuring points
     */
    public static function ringPercent(array $scores): ?int
    {
        return $scores === [] ? null : (int) round(array_sum($scores) / (count($scores) * 6) * 100);
    }

    /**
     * The step: the average PHI rounded down, 1 (nincs) … 6 (beépült) (18 · 18.4).
     *
     * @param  list<float>  $scores
     */
    public static function step(array $scores): ?int
    {
        $mean = self::mean($scores);

        return $mean === null ? null : max(1, min(6, (int) floor($mean + 1e-9)));
    }

    /**
     * "{pont} / {max}": the unrounded sum to a whole number, six per measuring point (plan 13).
     *
     * @param  list<float>  $scores
     * @return array{points: int, max: int}
     */
    public static function points(array $scores): array
    {
        return ['points' => (int) round(array_sum($scores)), 'max' => count($scores) * 6];
    }

    /**
     * Experience answers: "1"…"6" count, "nem tudom" and missing do not.
     *
     * @param  iterable<mixed>  $answers
     * @return list<float>
     */
    public static function numericAnswers(iterable $answers): array
    {
        $numbers = [];

        foreach ($answers as $answer) {
            if (is_numeric($answer) && (float) $answer >= 1 && (float) $answer <= 6) {
                $numbers[] = (float) $answer;
            }
        }

        return $numbers;
    }
}
