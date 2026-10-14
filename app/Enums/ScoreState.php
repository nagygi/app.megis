<?php

namespace App\Enums;

/**
 * State of a PHI score. "–" (no trace) is not a number and never counts in
 * averages (04, 18 · 18.4); the other states come with the engine (13, 18).
 */
enum ScoreState: string
{
    case Scored = 'scored';
    case NoTrace = 'no_trace';
    case InsufficientData = 'insufficient_data';
    case Pending = 'pending';

    public function label(): string
    {
        return __('enums.score_state.'.$this->value);
    }
}
