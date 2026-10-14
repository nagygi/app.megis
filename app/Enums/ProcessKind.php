<?php

namespace App\Enums;

/**
 * The type switch of a detected process (11 · 11.2).
 */
enum ProcessKind: string
{
    // "rutin": measured, goes into the diagnosis.
    case Routine = 'routine';
    // "egyszeri": among "Egyéb", a trace without its own score.
    case OneOff = 'one_off';
    // "nem mérjük": excluded, shown under "Kimaradt"; not offered for uploads (12).
    case Excluded = 'excluded';

    public function label(): string
    {
        return __('enums.process_kind.'.$this->value);
    }
}
