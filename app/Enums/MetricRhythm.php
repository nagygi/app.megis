<?php

namespace App\Enums;

/**
 * How often an own measure of a process is recorded (11 · 11.3).
 */
enum MetricRhythm: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    public function label(): string
    {
        return __('enums.metric_rhythm.'.$this->value);
    }
}
