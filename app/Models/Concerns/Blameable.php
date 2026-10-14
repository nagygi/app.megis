<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fills created_by and updated_by from the authenticated user of the current
 * guard, for entities editable from the admin (NFR · 7).
 */
trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function (Model $model): void {
            $actorId = auth()->id();

            if ($actorId !== null) {
                $model->created_by ??= $actorId;
                $model->updated_by ??= $actorId;
            }
        });

        static::updating(function (Model $model): void {
            $actorId = auth()->id();

            if ($actorId !== null) {
                $model->updated_by = $actorId;
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
