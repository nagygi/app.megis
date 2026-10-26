<?php

namespace App\Services\Report;

/**
 * Suffixes of the whole numbers 1–6 in the report's sentences: "5-nek
 * látjátok, 2-t mutattok", "3-nak", "5-öt" (27 · 27.5); the counts of the
 * group labels, "Öt érték".
 */
final class HungarianNumber
{
    private const ACCUSATIVE = [0 => '0-t', 1 => '1-et', 2 => '2-t', 3 => '3-at', 4 => '4-et', 5 => '5-öt', 6 => '6-ot'];

    private const DATIVE = [0 => '0-nak', 1 => '1-nek', 2 => '2-nek', 3 => '3-nak', 4 => '4-nek', 5 => '5-nek', 6 => '6-nak'];

    private const WORDS = [1 => 'egy', 2 => 'két', 3 => 'három', 4 => 'négy', 5 => 'öt', 6 => 'hat', 7 => 'hét', 8 => 'nyolc', 9 => 'kilenc', 10 => 'tíz'];

    /**
     * The count before a noun, "öt érték", "négy elem"; digits above ten.
     */
    public static function word(int $number): string
    {
        return self::WORDS[$number] ?? (string) $number;
    }

    public static function accusative(int $number): string
    {
        return self::ACCUSATIVE[$number] ?? $number.'-t';
    }

    public static function dative(int $number): string
    {
        return self::DATIVE[$number] ?? $number.'-nek';
    }

    /**
     * Per row, whole numbers with ,5 rounded up (18 · 18.4).
     */
    public static function rowValue(?float $number): ?int
    {
        return $number === null ? null : (int) floor($number + 0.5);
    }
}
