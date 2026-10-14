<?php

namespace App\Enums;

/**
 * Changes that show in a version history without creating a version (03 · 3.3).
 */
enum VersionEventType: string
{
    case Archived = 'archived';
    case Restored = 'restored';
    case Reviewed = 'reviewed';

    public function label(): string
    {
        return __('enums.version_event_type.'.$this->value);
    }
}
