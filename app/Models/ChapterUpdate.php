<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * "frissítve · {dátum}" on a chapter after a correction, or "· a ti
 * visszajelzésetek alapján" after incorporating feedback (21 · 21.4, 28 · 28.1).
 */
#[Fillable(['main_diagnosis_id', 'chapter', 'reason', 'created_by'])]
class ChapterUpdate extends Model
{
    public const UPDATED_AT = null;

    public const CHAPTERS = ['values', 'processes', 'areas', 'brand'];

    public const CORRECTION = 'correction';

    public const FEEDBACK = 'feedback';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
