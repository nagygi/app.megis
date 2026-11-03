<?php

namespace App\Services\Report;

use Illuminate\Support\Carbon;

/**
 * The lines of the Útravaló's numbered blocks (29 · 29.1), the same on the
 * page and in the PDF. A takeaway frozen before a field came has no such part.
 */
final class TakeawayText
{
    /**
     * "a helyzeteidben 6 · 3 helyzetből 3", or "az elején 5" without situations.
     *
     * @param  array<string, mixed>  $takeaway
     */
    public static function strength(array $takeaway): string
    {
        $row = $takeaway['strength'];

        return $row['situations'] !== null
            ? __('takeaway.strength_situations', [
                'score' => self::whole($row['situations']),
                'total' => $takeaway['situation_total'] ?? $row['situation_count'],
                'count' => $row['situation_count'],
            ])
            : __('takeaway.strength_start', ['score' => self::whole($row['start'])]);
    }

    /**
     * "a csapat öt értékén túl · Értéktükör · 10.14. · 2 helyzetben".
     *
     * @param  array<string, mixed>  $takeaway
     */
    public static function ownValue(array $takeaway): string
    {
        $value = $takeaway['own_value'];

        return collect([
            __('takeaway.own_value_line', ['count' => HungarianNumber::word((int) $value['team_count'])]),
            filled($value['mirror_on'] ?? null) ? __('takeaway.own_value_mirror', ['date' => Carbon::parse($value['mirror_on'])->format('m.d.')]) : null,
            ($value['situations'] ?? 0) > 0 ? __('takeaway.own_value_situations', ['count' => $value['situations']]) : null,
        ])->filter()->implode(' · ');
    }

    /**
     * "az elején 4, a helyzeteidben 2 · ★ fő eltérés · Ops heti".
     *
     * @param  array<string, mixed>  $takeaway
     */
    public static function selfGap(array $takeaway): string
    {
        $row = $takeaway['self_gap'];

        return collect([
            __('takeaway.self_gap_line', ['start' => self::whole($row['start']), 'situations' => self::whole($row['situations'])]),
            filled($row['process'] ?? null) ? __('takeaway.self_gap_process', ['process' => $row['process']]) : null,
        ])->filter()->implode(' · ');
    }

    public static function whole(?float $number): int|string
    {
        return $number === null ? '–' : (int) floor($number + 0.5);
    }
}
