<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A participant of a process: a user of the organization, or someone from
 * outside by name, who does not count in the headcount and gets no invitation (11 · 11.3).
 */
#[Fillable(['process_id', 'user_id', 'name', 'email', 'source'])]
class ProcessParticipant extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExternal(): bool
    {
        return $this->user_id === null;
    }

    public function displayName(): string
    {
        return $this->user?->name ?? $this->name ?? $this->email ?? '';
    }
}
