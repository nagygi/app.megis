<?php

namespace App\Services\Capabilities\Measurement;

use App\Enums\PreconditionValue;
use App\Enums\TraceDirection;

/**
 * The value of a precondition from the traces that speak about it (6.7):
 * only for it, megvan; only against it, nincs; both, részben; none, nem tudjuk.
 */
final class PreconditionCalculator
{
    /**
     * @param  list<TraceDirection>  $directions
     */
    public function value(array $directions): PreconditionValue
    {
        $positive = in_array(TraceDirection::Positive, $directions, true);
        $negative = in_array(TraceDirection::Negative, $directions, true);
        $mixed = in_array(TraceDirection::Mixed, $directions, true);

        return match (true) {
            $mixed || ($positive && $negative) => PreconditionValue::Partial,
            $positive => PreconditionValue::Present,
            $negative => PreconditionValue::Absent,
            default => PreconditionValue::Unknown,
        };
    }
}
