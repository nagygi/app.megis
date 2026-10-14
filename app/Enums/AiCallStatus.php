<?php

namespace App\Enums;

enum AiCallStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function label(): string
    {
        return __('enums.ai_call_status.'.$this->value);
    }
}
