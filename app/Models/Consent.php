<?php

namespace App\Models;

use App\Enums\ConsentKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A peer's consent with the exact text agreed to; withdrawing keeps the row (25 · 25.2, 31 · 31.6).
 */
#[Fillable(['user_id', 'main_diagnosis_id', 'kind', 'text_version', 'text', 'given_at', 'revoked_at'])]
class Consent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ConsentKind::class,
            'given_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MainDiagnosis, $this>
     */
    public function mainDiagnosis(): BelongsTo
    {
        return $this->belongsTo(MainDiagnosis::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
