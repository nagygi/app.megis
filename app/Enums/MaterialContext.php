<?php

namespace App\Enums;

/**
 * Where a material came in (06 · 6.1–6.3; occasions come with 12). A
 * dictation is a field spoken instead of typed: it is only transcribed
 * (08 · 8.1, 10 · 10.1, 24).
 */
enum MaterialContext: string
{
    case PublicMirror = 'public_mirror';
    case Mirror = 'mirror';
    case AdminUpload = 'admin_upload';
    case Dictation = 'dictation';
    // An occasion of a process: an upload or "Egy helyzet" (12).
    case Occasion = 'occasion';
    // A situation told to a value on the Képességek step: only capabilities are measured (NM-12).
    case CapabilityStory = 'capability_story';

    public function label(): string
    {
        return __('enums.material_context.'.$this->value);
    }

    public function isMirror(): bool
    {
        return in_array($this, [self::PublicMirror, self::Mirror], true);
    }
}
