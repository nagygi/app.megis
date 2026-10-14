<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasVersions;
use Database\Factories\PersonalValueSetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A user's own values (24). Common to every diagnosis the user takes part
 * in; publishing records which version a diagnosis used (25 · 25.7, 21 · 21.3).
 */
#[Fillable(['user_id', 'imported_mirror_session_id'])]
class PersonalValueSet extends Model
{
    /** @use HasFactory<PersonalValueSetFactory> */
    use Blameable, HasFactory, HasVersions;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MirrorSession, $this>
     */
    public function importedMirrorSession(): BelongsTo
    {
        return $this->belongsTo(MirrorSession::class, 'imported_mirror_session_id');
    }

    /**
     * @return HasMany<PersonalValueSetItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PersonalValueSetItem::class)->orderBy('position')->orderBy('id');
    }

    public function versionSnapshot(): array
    {
        return [
            'values' => $this->items()->get()->map(fn (PersonalValueSetItem $item) => [
                'key' => $item->key,
                'value_id' => $item->value_id,
                'source_version_id' => $item->source_version_id,
                'name' => $item->name,
                'meaning' => $item->meaning,
                'non_meaning' => $item->non_meaning,
                'low_example' => $item->low_example,
                'high_example' => $item->high_example,
                'example' => $item->example,
                'source' => $item->source,
            ])->all(),
        ];
    }
}
