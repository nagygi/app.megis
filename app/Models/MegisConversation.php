<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A conversation of the mégis with the client (19 · 19.5): when, with whom,
 * led by which admin. The leader sees the count and the next one on V10.
 */
#[Fillable(['main_diagnosis_id', 'held_at', 'with_kind', 'with_user_id', 'with_name', 'admin_id', 'note'])]
class MegisConversation extends Model
{
    use Blameable;

    public const WITH = ['leader', 'peer', 'external'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'held_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function withUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'with_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function withLabel(): string
    {
        return $this->withUser?->name ?? $this->with_name ?? __('admin.run.conversations.with.'.$this->with_kind);
    }
}
