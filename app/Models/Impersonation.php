<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A session in which an admin works in a customer's name (14 · 14.6).
 */
#[Fillable(['admin_id', 'user_id', 'started_at', 'ended_at', 'end_reason'])]
class Impersonation extends Model
{
    public const END_EXIT = 'exit';

    public const END_ADMIN_LOGOUT = 'admin_logout';

    public const END_ADMIN_SESSION_EXPIRED = 'admin_session_expired';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
