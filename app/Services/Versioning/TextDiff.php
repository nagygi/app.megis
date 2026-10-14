<?php

namespace App\Services\Versioning;

/**
 * Word level comparison of two texts (03 · 3.3). Whitespace is kept, so the
 * segments put together give back the original texts.
 */
class TextDiff
{
    public const EQUAL = 'equal';

    public const INSERT = 'insert';

    public const DELETE = 'delete';

    /**
     * @return list<array{type: string, text: string}>
     */
    public static function compare(string $old, string $new): array
    {
        $oldTokens = self::tokens($old);
        $newTokens = self::tokens($new);
        $segments = [];
        $i = 0;
        $j = 0;

        foreach (Lcs::pairs($oldTokens, $newTokens) as [$oldIndex, $newIndex]) {
            for (; $i < $oldIndex; $i++) {
                self::append($segments, self::DELETE, $oldTokens[$i]);
            }

            for (; $j < $newIndex; $j++) {
                self::append($segments, self::INSERT, $newTokens[$j]);
            }

            self::append($segments, self::EQUAL, $oldTokens[$i]);
            $i++;
            $j++;
        }

        for (; $i < count($oldTokens); $i++) {
            self::append($segments, self::DELETE, $oldTokens[$i]);
        }

        for (; $j < count($newTokens); $j++) {
            self::append($segments, self::INSERT, $newTokens[$j]);
        }

        return $segments;
    }

    /**
     * @return list<string>
     */
    private static function tokens(string $text): array
    {
        return preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @param  list<array{type: string, text: string}>  $segments
     */
    private static function append(array &$segments, string $type, string $text): void
    {
        $last = array_key_last($segments);

        if ($last !== null && $segments[$last]['type'] === $type) {
            $segments[$last]['text'] .= $text;

            return;
        }

        $segments[] = ['type' => $type, 'text' => $text];
    }
}
