<?php

namespace App\Enums;

enum CheckStatus: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Error = 'error';

    public function label(): string
    {
        return __('checks.status.'.$this->value);
    }
}
