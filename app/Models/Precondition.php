<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One of the fixed preconditions E1–E9 (07 · 7.6): a structural condition
 * without which a capability does not work, even when people can do it.
 * Read only, it comes with the schema.
 */
class Precondition extends Model
{
    /**
     * The precondition patterns that show it (NM-19).
     *
     * @return BelongsToMany<Pattern, $this>
     */
    public function patterns(): BelongsToMany
    {
        return $this->belongsToMany(Pattern::class)->withPivot(['note', 'position']);
    }

    public function label(): string
    {
        return $this->code.' · '.$this->name;
    }
}
