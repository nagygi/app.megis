<?php

namespace App\Enums;

/**
 * Whether a value is an item of Schwartz's Value Survey (03 · 3.2).
 */
enum ValueSvsStatus: string
{
    case Yes = 'yes';
    // In the survey, but outside the scoring of the basic values.
    case XCoded = 'x_coded';
    case ToVerify = 'to_verify';

    public function label(): string
    {
        return __('enums.value_svs_status.'.$this->value);
    }
}
