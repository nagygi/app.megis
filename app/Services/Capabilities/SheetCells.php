<?php

namespace App\Services\Capabilities;

use Illuminate\Support\Str;

/**
 * How the cells of the mégis tár are read (07 · 7.6, NM-19): the three empty
 * values, the "[JAVASLAT]" prefix, list cells and code links. Shared by the
 * Képesség, Minta and Szabálykönyv loads.
 *
 * The entry is the row being built: its "states" collect the empty values
 * by field, its "proposed" the fields marked "[JAVASLAT]".
 */
final class SheetCells
{
    /**
     * The empty values of the Sheet (kepessegrendszer_osszefoglalo.md · 7).
     */
    public const EMPTY_STATES = [
        'nincs elég külső bizonyíték' => 'insufficient_evidence',
        'nem értelmezhető' => 'not_applicable',
        'nincs még' => 'not_yet',
    ];

    /**
     * A precondition (E1–E9) or another capability (RC-xx, ÚJ-xx) with its
     * note in parentheses; a code inside someone else's parentheses is only a mention.
     */
    public const LINK_PATTERN = '/(?<paren>\((?:[^()]++|(?&paren))*\))|(?<![\p{L}\p{N}-])(?<code>E[1-9]|RC-\d{2,3}|[ÚU]J-\d{2,3})(?![\p{L}\p{N}-])(?:\s*(?<note>(?&paren)))?/u';

    /**
     * A text cell: an empty value of the Sheet becomes empty with its state,
     * a "[JAVASLAT]" prefix becomes a mark on the field.
     *
     * @param  array<string, mixed>  $entry
     */
    public static function text(string $raw, string $field, array &$entry): string
    {
        $text = trim($raw);
        $state = self::emptyState($text);

        if ($state !== null) {
            $entry['states'][$field] = $state;

            return '';
        }

        if (preg_match('/^\[javaslat\]\s*/iu', $text) === 1) {
            $entry['proposed'][] = $field;
            $text = trim((string) preg_replace('/^\[javaslat\]\s*/iu', '', $text));
        }

        return $text;
    }

    /**
     * A list cell: one item per line, without bullets.
     *
     * @param  array<string, mixed>  $entry
     * @return list<string>
     */
    public static function items(string $raw, string $field, array &$entry): array
    {
        $text = trim($raw);
        $state = self::emptyState($text);

        if ($state !== null) {
            $entry['states'][$field] = $state;

            return [];
        }

        $items = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $item = trim((string) preg_replace('/^\s*(?:[-•*–]|\d+[.)])\s+/u', '', $line));

            if (preg_match('/^\[javaslat\]\s*/iu', $item) === 1) {
                $entry['proposed'][] = $field;
                $item = trim((string) preg_replace('/^\[javaslat\]\s*/iu', '', $item));
            }

            if ($item !== '' && ! in_array($item, $items, true)) {
                $items[] = $item;
            }
        }

        $entry['proposed'] = array_values(array_unique($entry['proposed']));

        return $items;
    }

    /**
     * The codes of a cell with their notes, each code once.
     *
     * @return list<array{code: string, note: string|null}>
     */
    public static function links(string $text): array
    {
        preg_match_all(self::LINK_PATTERN, $text, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);
        $links = [];

        foreach ($matches as $match) {
            if (($match['code'] ?? null) === null) {
                continue;
            }

            $code = self::code($match['code']);
            $note = $match['note'] === null ? null : trim(mb_substr($match['note'], 1, -1));

            if (isset($links[$code])) {
                $links[$code]['note'] = collect([$links[$code]['note'], $note])->filter()->implode('; ') ?: null;

                continue;
            }

            $links[$code] = ['code' => $code, 'note' => filled($note) ? $note : null];
        }

        return array_values($links);
    }

    /**
     * What the codes and their notes of a cell do not say.
     */
    public static function linkRest(string $text): string
    {
        return trim((string) preg_replace(self::LINK_PATTERN, '', $text), " \t\n\r,;|.");
    }

    public static function emptyState(string $text): ?string
    {
        $lower = mb_strtolower($text);

        foreach (self::EMPTY_STATES as $prefix => $state) {
            if (str_starts_with($lower, $prefix)) {
                return $state;
            }
        }

        return null;
    }

    public static function code(string $code): string
    {
        $code = mb_strtoupper(trim($code));

        return (string) preg_replace('/^UJ-/u', 'ÚJ-', $code);
    }

    /**
     * Compared without case, accents and punctuation.
     */
    public static function normalize(string $text): string
    {
        return Str::of($text)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->toString();
    }
}
