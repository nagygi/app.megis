<?php

namespace App\Enums;

enum OrganizationType: string
{
    case Organization = 'organization';
    case Individual = 'individual';

    public function label(): string
    {
        return __('enums.organization_type.'.$this->value);
    }
}
