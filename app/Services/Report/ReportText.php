<?php

namespace App\Services\Report;

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
            return trans_choice($meta['threshold_overridden'] ? 'report.threshold_overridden' : 'report.threshold_missing', max(1, $meta['missing_peers']), ['count' => max(1, $meta['missing_peers'])]);
        }

        if ($row['experience_whole'] === null || $row['gap_against_whole'] === null) {
            return null;
        }

        return __($meta['has_peers'] ? 'report.gap_sentence' : 'report.gap_sentence_self', [
            'experience' => HungarianNumber::dative($row['experience_whole']),
            'score' => $meta['has_peers'] ? HungarianNumber::accusative($row['gap_against_whole']) : HungarianNumber::dative($row['gap_against_whole']),
        ]);
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
