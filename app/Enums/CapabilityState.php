<?php

namespace App\Enums;

/**
 * Is the capability there now (6.2, AL-01): always for a level and a period.
 * "Nincs nyoma": there was a source it could have shown in; "nincs adat": there was none.
 */
enum CapabilityState: string
{
    case Present = 'present';
    case Absent = 'absent';
    case NoTrace = 'no_trace';
    case NoData = 'no_data';

    public function label(): string
    {
        return __('enums.capability_state.'.$this->value);
    }
}
