<?php

namespace App\Enums;

/**
 * Where a material came in (06 · 6.1–6.3; occasions come with 12).
 */
enum MaterialContext: string
{
    case PublicMirror = 'public_mirror';
    case Mirror = 'mirror';
    case AdminUpload = 'admin_upload';

    public function label(): string
    {
        return __('enums.material_context.'.$this->value);
    }

    public function isMirror(): bool
    {
        return $this !== self::AdminUpload;
    }
}
