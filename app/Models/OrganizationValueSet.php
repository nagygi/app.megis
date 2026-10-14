<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasVersions;
use Database\Factories\OrganizationValueSetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The organization's values with the brand base (08). Where it is used —
 * a diagnosis, an AI analysis — the version is recorded (08 · 8.4).
 */
#[Fillable(['organization_id', 'purpose', 'value_proposition', 'impact'])]
class OrganizationValueSet extends Model
{
    /** @use HasFactory<OrganizationValueSetFactory> */
    use Blameable, HasFactory, HasVersions;

    public const MIN_VALUES = 3;

    public const MAX_VALUES = 5;

    /**
     * The brand base elements with a text; the root is the value list (08 · 8.2).
     */
    public const BRAND_FIELDS = ['purpose', 'value_proposition', 'impact'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<OrganizationValueSetItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrganizationValueSetItem::class)->orderBy('position')->orderBy('id');
    }

    public function versionSnapshot(): array
    {
        return [
            'values' => $this->items()->get()->map(fn (OrganizationValueSetItem $item) => [
                'key' => $item->key,
                'value_id' => $item->value_id,
                'source_version_id' => $item->source_version_id,
                'name' => $item->name,
                'meaning' => $item->meaning,
                'non_meaning' => $item->non_meaning,
                'low_example' => $item->low_example,
                'high_example' => $item->high_example,
                'example' => $item->example,
            ])->all(),
            'purpose' => $this->purpose,
            'value_proposition' => $this->value_proposition,
            'impact' => $this->impact,
        ];
    }
}
