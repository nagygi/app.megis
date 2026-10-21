<?php

namespace App\Services\Capabilities;

/**
 * Takes the cases of the mégis tár out of a text before it goes to the AI
 * (NM-12): the client names (DXC, SZV …) and the codes of the case sources
 * (DXCK-23, DXC-01) and of the tár rows (T-0001, B-0001, K-0001, M-001).
 */
final class CaseLabelScrubber
{
    /**
     * @param  list<string>  $labels
     */
    public function __construct(private array $labels = []) {}

    public static function fromConfig(): self
    {
        return new self((array) config('megis.capabilities.case_labels', []));
    }

    public function scrub(?string $text): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        // Codes first, so "DXCK-23" goes whole, not as "DXC" and a remainder.
        $text = (string) preg_replace('/(?<![\p{L}\p{N}])[A-ZÁÉÍÓÖŐÚÜŰ]{2,6}-\d{1,4}(?![\p{L}\p{N}])/u', '', $text);
        $text = (string) preg_replace('/(?<![\p{L}\p{N}])[TBKM]-\d{3,4}(?![\p{L}\p{N}])/u', '', $text);

        foreach ($this->labels as $label) {
            $text = (string) preg_replace('/(?<![\p{L}\p{N}])'.preg_quote($label, '/').'(?![\p{L}\p{N}])/u', '', $text);
        }

        // What the removal left behind: empty parentheses and doubled spaces or commas.
        $text = (string) preg_replace('/\(\s*[,;:]*\s*\)|\[\s*\]/u', '', $text);
        $text = (string) preg_replace('/\s+([,.;:])/u', '$1', $text);
        $text = (string) preg_replace('/([,;:])(?:\s*[,;:])+/u', '$1', $text);

        return trim((string) preg_replace('/[ \t]{2,}/u', ' ', $text));
    }
}
