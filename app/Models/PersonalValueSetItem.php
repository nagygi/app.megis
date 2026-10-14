<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One own value in the user's words (24 · 24.2, 24.3). The Értéktár value
 * does not change when the user rewrites the definition.
 */
#[Fillable([
    'key', 'value_id', 'source_version_id', 'name', 'meaning', 'non_meaning',
    'low_example', 'high_example', 'example', 'source', 'quotes', 'position',
])]
class PersonalValueSetItem extends Model
{
    public const SOURCE_MIRROR = 'mirror';

    public const SOURCE_LIBRARY = 'library';

    public const SOURCE_NEW = 'new';

    /**
     * The fields of V2·c (24 · 24.3).
     */
    public const TEXT_FIELDS = ['meaning', 'non_meaning', 'low_example', 'high_example', 'example'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quotes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PersonalValueSet, $this>
     */
    public function valueSet(): BelongsTo
    {
        return $this->belongsTo(PersonalValueSet::class, 'personal_value_set_id');
    }

    /**
     * @return BelongsTo<Value, $this>
     */
    public function value(): BelongsTo
    {
        return $this->belongsTo(Value::class);
    }
}
