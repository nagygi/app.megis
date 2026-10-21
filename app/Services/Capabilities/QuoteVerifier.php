<?php

namespace App\Services\Capabilities;

use Illuminate\Support\Str;

/**
 * Checks that a quote of the AI is in the material word for word (6.1:
 * the quote is checked against the original). "[...]" stands for a cut part
 * and "[név]" for a removed name; spacing, case and quotation marks do not count.
 */
final class QuoteVerifier
{
    public function occursIn(string $quote, string $source): bool
    {
        $parts = preg_split('/\[(?:\.\.\.|…|név)\]/u', $quote) ?: [];
        $haystack = self::normalize($source);
        $offset = 0;
        $found = false;

        foreach ($parts as $part) {
            $needle = self::normalize($part);

            if ($needle === '') {
                continue;
            }

            $position = mb_strpos($haystack, $needle, $offset);

            if ($position === false) {
                return false;
            }

            $offset = $position + mb_strlen($needle);
            $found = true;
        }

        return $found;
    }

    private static function normalize(string $text): string
    {
        $text = Str::lower($text);
        $text = str_replace(['„', '”', '“', '"', '»', '«', '’', '‘', '`'], '', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text), " \t\n\r.,;:!?–-");
    }
}
