<?php

namespace App\Services\Engine;

/**
 * Splits a long transcript at line breaks into parts of at most the given
 * length, so step A reads it in several calls (06 · 6.4). A single line
 * longer than the limit is split at sentence ends, then hard.
 */
class TranscriptChunker
{
    /**
     * @return list<string>
     */
    public function chunks(string $transcript, int $maxCharacters): array
    {
        $transcript = trim($transcript);

        if ($transcript === '') {
            return [];
        }

        $chunks = [];
        $current = '';

        foreach ($this->pieces($transcript, $maxCharacters) as $piece) {
            $candidate = $current === '' ? $piece : $current."\n".$piece;

            if (mb_strlen($candidate) > $maxCharacters && $current !== '') {
                $chunks[] = $current;
                $current = $piece;

                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * @return list<string>
     */
    private function pieces(string $transcript, int $maxCharacters): array
    {
        $pieces = [];

        foreach (preg_split('/\R/u', $transcript) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (mb_strlen($line) <= $maxCharacters) {
                $pieces[] = $line;

                continue;
            }

            foreach (preg_split('/(?<=[.!?…])\s+/u', $line) ?: [] as $sentence) {
                foreach (mb_str_split($sentence, $maxCharacters) as $part) {
                    $pieces[] = $part;
                }
            }
        }

        return $pieces;
    }
}
