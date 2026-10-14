<?php

namespace App\Enums;

/**
 * Processing state of a material: transcription, speaker assignment (admin
 * upload only), value measurement.
 */
enum MaterialStatus: string
{
    case Pending = 'pending';
    case Transcribing = 'transcribing';
    case AwaitingSpeakers = 'awaiting_speakers';
    case Analyzing = 'analyzing';
    case Processed = 'processed';
    case Failed = 'failed';

    public function label(): string
    {
        return __('enums.material_status.'.$this->value);
    }

    public function isWorking(): bool
    {
        return in_array($this, [self::Pending, self::Transcribing, self::Analyzing], true);
    }
}
