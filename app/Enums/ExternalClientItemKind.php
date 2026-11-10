<?php

namespace App\Enums;

/**
 * What a client is asked about (35 · 35.3): one of the organization's values
 * or one of its processes.
 */
enum ExternalClientItemKind: string
{
    case Value = 'value';
    case Process = 'process';

    public function label(): string
    {
        return __('enums.external_client_item_kind.'.$this->value);
    }
}
