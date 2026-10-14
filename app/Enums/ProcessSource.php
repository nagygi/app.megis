<?php

namespace App\Enums;

/**
 * Where a process came from (11 · 11.6): the calendar (09), the conversation (10), by hand.
 */
enum ProcessSource: string
{
    case Calendar = 'calendar';
    case Conversation = 'conversation';
    case Manual = 'manual';

    public function label(): string
    {
        return __('enums.process_source.'.$this->value);
    }
}
