<?php

namespace App\Enums;

/**
 * State of a self-reported experience: 1–6, "nem tudom", or no answer (04 · 4.4).
 */
enum ExperienceState: string
{
    case Answered = 'answered';
    case DontKnow = 'dont_know';
    case Missing = 'missing';

    public function label(): string
    {
        return __('enums.experience_state.'.$this->value);
    }
}
