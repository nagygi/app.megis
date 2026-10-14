<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One value of an organization in its own words (08 · 8.1). The Értéktár
 * value does not change when the organization rewrites the definition.
 */
#[Fillable([
    'key', 'value_id', 'source_version_id', 'name',
    'meaning', 'non_meaning', 'low_example', 'high_example', 'example', 'position',
])]
class OrganizationValueSetItem extends Model
{
    /**
     * Fields of the value card, as on V2·c and V3 (08 · 8.1).
     */
    public const TEXT_FIELDS = ['meaning', 'non_meaning', 'low_example', 'high_example', 'example'];

    /**
     * @return BelongsTo<OrganizationValueSet, $this>
     */
    public function valueSet(): BelongsTo
    {
        return $this->belongsTo(OrganizationValueSet::class, 'organization_value_set_id');
    }

    /**
     * @return BelongsTo<Value, $this>
     */
    public function value(): BelongsTo
    {
        return $this->belongsTo(Value::class);
    }

    /**
     * @return BelongsTo<Version, $this>
     */
    public function sourceVersion(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'source_version_id');
    }
}
