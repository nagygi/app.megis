<?php

namespace App\Services\Versioning;

/**
 * Longest common subsequence of two token lists (03 · 3.3 comparison).
 */
class Lcs
{
    /**
     * Index pairs [old index, new index] of the common subsequence, in order.
     *
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<array{0: int, 1: int}>
     */
    public static function pairs(array $old, array $new): array
    {
        $oldCount = count($old);
        $newCount = count($new);
        $lengths = array_fill(0, $oldCount + 1, array_fill(0, $newCount + 1, 0));

        for ($i = $oldCount - 1; $i >= 0; $i--) {
            for ($j = $newCount - 1; $j >= 0; $j--) {
                $lengths[$i][$j] = $old[$i] === $new[$j]
                    ? $lengths[$i + 1][$j + 1] + 1
                    : max($lengths[$i + 1][$j], $lengths[$i][$j + 1]);
            }
        }

        $pairs = [];
        $i = 0;
        $j = 0;

        while ($i < $oldCount && $j < $newCount) {
            if ($old[$i] === $new[$j]) {
                $pairs[] = [$i++, $j++];
            } elseif ($lengths[$i + 1][$j] >= $lengths[$i][$j + 1]) {
                $i++;
            } else {
                $j++;
            }
        }

        return $pairs;
    }
}
