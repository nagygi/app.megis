<?php

namespace App\Enums;

/**
 * A marker beside the level, not a level of its own (6.2): between two units,
 * or a micro organization of 1–3 people where team and organization coincide.
 */
enum LevelMarker: string
{
    case Boundary = 'boundary';
    case Micro = 'micro';

    public function label(): string
    {
        return __('enums.level_marker.'.$this->value);
    }
}
