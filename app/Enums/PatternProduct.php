<?php

namespace App\Enums;

/**
 * The products a pattern is used in ("termékek").
 */
enum PatternProduct: string
{
    case Full = 'full';
    case SnapshotInternal = 'snapshot_internal';
    case SnapshotExternal = 'snapshot_external';
    case ValueMeasurement = 'value_measurement';
    case Bridge = 'bridge';
    case OperatingSystem = 'operating_system';
    case Mirror = 'mirror';
    case Testpad = 'testpad';
    case Detector = 'detector';

    public function label(): string
    {
        return __('enums.pattern_product.'.$this->value);
    }
}
