<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The trace of an anonymous mirror after 24 hours: free name and match,
 * no quote, no person (06 · 6.1).
 */
#[Fillable(['free_name', 'value_id', 'confidence', 'recorded_on'])]
class AnonymousValueStat extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'recorded_on' => 'date',
        ];
    }
}
