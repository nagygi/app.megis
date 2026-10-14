<?php

namespace App\Enums;

/**
 * "magas / közepes / alacsony / kézzel / beszélgetésből" on the card (11 · 11.2).
 */
enum ProcessConfidence: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Manual = 'manual';
    case Conversation = 'conversation';

    public function label(): string
    {
        return __('enums.process_confidence.'.$this->value);
    }
}
