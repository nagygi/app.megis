<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A load from the mégis tár (07 · 7.6): who, when, from which file, with
 * what result. The file itself is not kept.
 */
#[Fillable(['file_name', 'created_count', 'updated_count', 'unchanged_count', 'skipped_count', 'created_by'])]
class CapabilityImport extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
