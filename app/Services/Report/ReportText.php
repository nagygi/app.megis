<?php

namespace App\Services\Report;

use Illuminate\Support\Facades\Lang;

/**
 * The small sentences of the report built from a snapshot row (27 · 27.1):
 * the PHI as the leader reads it, and the gap or the threshold next to it.
 */
final class ReportText
{
    /**
     * "4", "–" (nincs nyoma) or "nincs elég adat"; whole numbers per row (18 · 18.4).
     *
     * @param  array<string, mixed>  $row
     */
    public static function score(array $row): string
    {
        return match ($row['score_state'] ?? null) {
            'scored' => (string) $row['score_whole'],
            'insufficient_data' => __('report.insufficient'),
            default => __('report.no_trace'),
        };
    }

    /**
     * "5-nek látjátok, 2-t mutattok.", or the grey threshold sentence (18 · 18.6).
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $meta
     */
    public static function gap(array $row, array $meta): ?string
    {
        if (in_array($row['kind'], ['area', 'process'], true)) {
            return null;
        }

        if (! $meta['shows_experience']) {
            return self::missingPeers($meta);
        }

        if ($row['experience_whole'] === null || $row['gap_against_whole'] === null) {
            return null;
        }

        // A standalone diagnosis sets the own experience against the score (04 · 4.4).
        if ($meta['standalone'] ?? false) {
            return self::copy('gap_sentence', $meta, [
                'experience' => HungarianNumber::dative($row['experience_whole']),
                'score' => HungarianNumber::accusative($row['gap_against_whole']),
            ]);
        }

        return __($meta['has_peers'] ? 'report.gap_sentence' : 'report.gap_sentence_self', [
            'experience' => HungarianNumber::dative($row['experience_whole']),
            'score' => $meta['has_peers'] ? HungarianNumber::accusative($row['gap_against_whole']) : HungarianNumber::dative($row['gap_against_whole']),
        ]);
    }

    /**
     * A sentence of the report for its reader (04 · 4.6): a user's own
     * diagnosis speaks to one person, a standalone one has its own words.
     *
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $replace
     */
    public static function copy(string $key, array $meta, array $replace = []): string
    {
        $prefixes = array_filter([
            ($meta['voice'] ?? null) === 'self' ? 'report.self.' : null,
            ($meta['standalone'] ?? false) ? 'report.standalone.' : null,
        ]);

        foreach ($prefixes as $prefix) {
            if (Lang::has($prefix.$key)) {
                return __($prefix.$key, $replace);
            }
        }

        return __('report.'.$key, $replace);
    }

    /**
     * "Miből" of a standalone diagnosis: its material, the users' diagnoses
     * taken together (04 · 4.5), or what the mégis recorded by hand.
     *
     * @param  array{kind: string, type?: string, count?: int}  $source
     */
    public static function standaloneSource(array $source): string
    {
        return match ($source['kind']) {
            'material' => __('report.trace.sources.'.$source['type']),
            'aggregated' => __('appendix.from_aggregated', ['count' => $source['count']]),
            default => __('appendix.from_manual'),
        };
    }

    /**
     * "Még 1 társ hangja kell(ett volna), hogy lássátok a rést." (Extra inputok 1).
     *
     * @param  array<string, mixed>  $meta
     */
    public static function missingPeers(array $meta): string
    {
        $count = max(1, $meta['missing_peers']);

        return trans_choice($meta['threshold_overridden'] ? 'report.threshold_overridden' : 'report.threshold_missing', $count, ['count' => $count]);
    }

    public static function decimal(?float $number): string
    {
        return $number === null ? '–' : number_format($number, 1, ',', '');
    }

    /**
     * "töréspont" in the internal snapshot, "repedés" otherwise (18 · 18.3).
     *
     * @param  array<string, mixed>  $meta
     */
    public static function claimKind(string $kind, array $meta): string
    {
        return __('report.claim_kinds.'.($kind === 'crack' && $meta['snapshot'] ? 'breakpoint' : $kind));
    }
}
