<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A load of the Értéktár from a table (03 · 3.6), from the Érték Import or
 * the command: who, when, from which file, with what result. The file itself
 * is not kept.
 */
#[Fillable(['file_name', 'created_count', 'updated_count', 'unchanged_count', 'categories_created_count', 'created_by'])]
class ValueImport extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
