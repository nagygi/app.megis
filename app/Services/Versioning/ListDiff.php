<?php

namespace App\Services\Versioning;

/**
 * Compares two versions of an ordered list: kept, added, removed and moved
 * items (03 · 3.3). An item that is in both lists but out of the common order
 * counts as moved.
 */
class ListDiff
{
    public const UNCHANGED = 'unchanged';

    public const ADDED = 'added';

    public const REMOVED = 'removed';

    public const MOVED = 'moved';

    /**
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return array{old: list<array{text: string, status: string}>, new: list<array{text: string, status: string}>}
     */
    public static function compare(array $old, array $new): array
    {
        $pairs = Lcs::pairs($old, $new);
        $keptOld = array_column($pairs, 0);
        $keptNew = array_column($pairs, 1);

        $leftoverOld = self::countTexts(array_diff_key($old, array_flip($keptOld)));
        $leftoverNew = self::countTexts(array_diff_key($new, array_flip($keptNew)));

        return [
            'old' => self::mark($old, $keptOld, $leftoverNew, self::REMOVED),
            'new' => self::mark($new, $keptNew, $leftoverOld, self::ADDED),
        ];
    }

    /**
     * @param  list<string>  $items
     * @param  list<int>  $kept
     * @param  array<string, int>  $leftoverOnOtherSide
     * @return list<array{text: string, status: string}>
     */
    private static function mark(array $items, array $kept, array $leftoverOnOtherSide, string $missingStatus): array
    {
        $kept = array_flip($kept);
        $marked = [];

        foreach ($items as $index => $text) {
            if (isset($kept[$index])) {
                $status = self::UNCHANGED;
            } elseif (($leftoverOnOtherSide[$text] ?? 0) > 0) {
                $leftoverOnOtherSide[$text]--;
                $status = self::MOVED;
            } else {
                $status = $missingStatus;
            }

            $marked[] = ['text' => $text, 'status' => $status];
        }

        return $marked;
    }

    /**
     * @param  array<int, string>  $items
     * @return array<string, int>
     */
    private static function countTexts(array $items): array
    {
        $counts = [];

        foreach ($items as $text) {
            $counts[$text] = ($counts[$text] ?? 0) + 1;
        }

        return $counts;
    }
}
