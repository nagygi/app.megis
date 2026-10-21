<?php

namespace App\Enums;

/**
 * The three kinds of pattern (NM-19, kepessegrendszer_osszefoglalo.md · 6.6). A detector
 * error is never shown to a client: it is a prohibition for the recognition.
 */
enum PatternType: string
{
    case Capability = 'capability';
    case Precondition = 'precondition';
    case DetectorError = 'detector_error';

    public function label(): string
    {
        return __('enums.pattern_type.'.$this->value);
    }
}
