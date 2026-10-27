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
     * "3-ban", "2-ben": by how the last spoken part sounds ("három", "kettő",
     * "húsz", "száz"), as in "3 helyzetedből 3-ban" (prototype V2·b).
     */
    public static function inessive(int $number): string
    {
        return $number.'-'.(self::isBack($number) ? 'ban' : 'ben');
    }

    /**
     * "a 7-ből", "az 1-ből", "a 14-ből": out of how many (prototype V10, T5).
     */
    public static function outOf(int $number): string
    {
        return self::article($number).' '.$number.'-'.(self::isBack($number) ? 'ból' : 'ből');
    }

    /**
     * "az" before the numbers said with a vowel first: egy, öt, ötven.
     */
    public static function article(int $number): string
    {
        return in_array($number, [1, 5], true) || ($number >= 50 && $number <= 59) ? 'az' : 'a';
    }

    /**
     * Whether the last spoken part of the number takes back vowels:
     * "három", "hat", "nyolc", "húsz", "harminc", "száz".
     */
    private static function isBack(int $number): bool
    {
        return match (true) {
            $number % 10 !== 0 => in_array($number % 10, [3, 6, 8], true),
            $number % 100 !== 0 => in_array(intdiv($number % 100, 10), [2, 3, 6, 8], true),
            default => $number === 0 || $number % 1000 !== 0,
        };
    }

    /**
     * Per row, whole numbers with ,5 rounded up (18 · 18.4).
     */
    public static function rowValue(?float $number): ?int
    {
        return $number === null ? null : (int) floor($number + 0.5);
    }
}
