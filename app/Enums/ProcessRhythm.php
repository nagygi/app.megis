<?php

namespace App\Enums;

/**
 * The rhythm of a process added by hand (11 · 11.2).
 */
enum ProcessRhythm: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Occasional = 'occasional';

    public function label(): string
    {
        return __('enums.process_rhythm.'.$this->value);
    }
}
