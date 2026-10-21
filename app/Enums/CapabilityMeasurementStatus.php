<?php

namespace App\Enums;

/**
 * Where the capability measurement of a material stands (NM-12).
 */
enum CapabilityMeasurementStatus: string
{
    case Pending = 'pending';
    case Analyzing = 'analyzing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __('enums.capability_measurement_status.'.$this->value);
    }
}
