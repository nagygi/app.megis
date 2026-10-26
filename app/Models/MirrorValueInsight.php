<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ET4 · one value of a mirror in the user's own words: what it means, where
 * it shows, the two ends of the scale (NM-06). The mégis Coach suggests it,
 * the user takes it ("Ez az") or rewrites the meaning ("Átírom").
 */
#[Fillable(['group_key', 'meaning', 'signs', 'low_example', 'high_example', 'accepted_at', 'edited_at', 'ai_call_id'])]
class MirrorValueInsight extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signs' => 'array',
            'accepted_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MirrorSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(MirrorSession::class, 'mirror_session_id');
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }
}
