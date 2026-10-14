<?php

namespace App\Services\Materials;

/**
 * Speaker turns of an uploaded transcript (06 · 6.3, 12 · 12.6): existing
 * labels are kept ("Anna:", "Beszélő 2:", "[00:12:03] Anna:"); without labels
 * the whole text is one speaker.
 */
class TranscriptParser
{
    public const SINGLE_SPEAKER = 'Beszélő 1';

    /**
     * A label is at most four words before a colon, optionally after a time stamp.
     */
    private const LABEL = '/^\s*(?:\[?\(?\d{1,2}:\d{2}(?::\d{2})?\)?\]?\s*[-–]?\s*)?([\p{L}][\p{L}\p{N}.\'’ -]{0,40}?)\s*(?:\(\d{1,2}:\d{2}(?::\d{2})?\))?\s*:\s+(.+)$/u';

    /**
     * @return list<array{speaker: string, text: string}>
     */
    public function parse(string $text): array
    {
        $segments = [];
        $current = null;

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            if (preg_match(self::LABEL, $line, $match) === 1 && str_word_count($match[1], 0, 'áéíóöőúüűÁÉÍÓÖŐÚÜŰ') <= 4) {
                if ($current !== null) {
                    $segments[] = $current;
                }

                $current = ['speaker' => trim($match[1]), 'text' => trim($match[2])];

                continue;
            }

            if ($current === null) {
                $current = ['speaker' => '', 'text' => trim($line)];
            } else {
                $current['text'] .= "\n".trim($line);
            }
        }

        if ($current !== null) {
            $segments[] = $current;
        }

        $labelled = array_filter($segments, fn (array $segment): bool => $segment['speaker'] !== '');

        // Without real labels the whole text belongs to one speaker.
        if (count($labelled) < 2 && count(array_unique(array_column($labelled, 'speaker'))) < 2) {
            return [['speaker' => self::SINGLE_SPEAKER, 'text' => trim($text)]];
        }

        return array_values(array_map(
            fn (array $segment): array => [
                'speaker' => $segment['speaker'] === '' ? self::SINGLE_SPEAKER : $segment['speaker'],
                'text' => $segment['text'],
            ],
            $segments,
        ));
    }
}
