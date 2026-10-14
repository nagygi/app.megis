<?php

namespace App\Enums;

/**
 * How the content arrived: recorded in the browser, typed, or an uploaded file.
 */
enum MaterialSource: string
{
    case Recording = 'recording';
    case Text = 'text';
    case AudioUpload = 'audio_upload';
    case TextUpload = 'text_upload';

    public function label(): string
    {
        return __('enums.material_source.'.$this->value);
    }

    public function hasAudio(): bool
    {
        return in_array($this, [self::Recording, self::AudioUpload], true);
    }
}
