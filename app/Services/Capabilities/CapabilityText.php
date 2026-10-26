<?php

namespace App\Services\Capabilities;

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityState;

/**
 * The words of a capability row for its readers (V12, M3): "Jelen lehet ·
 * közepes · 4 nyom", low confidence as "kevés még az adat" (KO-03).
 */
class CapabilityText
{
    /**
     * @param  array{state: CapabilityState, confidence: ?CapabilityConfidence, little_data: bool, traces: int, uneven: bool}  $row
     */
    public static function line(array $row): string
    {
        return collect([
            mb_ucfirst($row['state']->label()),
            self::confidence($row),
            $row['traces'] > 0 ? trans_choice('capabilities.library.traces', $row['traces'], ['count' => $row['traces']]) : null,
            $row['uneven'] ? __('capabilities.library.uneven') : null,
        ])->filter()->implode(' · ');
    }

    /**
     * The confidence in a word; the low one says why.
     *
     * @param  array{confidence: ?CapabilityConfidence, little_data: bool}  $row
     */
    public static function confidence(array $row): ?string
    {
        if ($row['little_data']) {
            return __('capabilities.library.little_data');
        }

        return $row['confidence']?->label();
    }
}
