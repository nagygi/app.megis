<?php

namespace App\Models;

use App\Enums\TokenPurpose;
use Database\Factories\UserTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-time link (and optionally a code) sent by e-mail. Only hashes are stored.
 */
#[Fillable(['user_id', 'purpose', 'organization_id', 'token_hash', 'code_hash', 'expires_at', 'created_by'])]
class UserToken extends Model
{
    /** @use HasFactory<UserTokenFactory> */
    use HasFactory, MassPrunable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => TokenPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'invalidated_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('consumed_at')
            ->whereNull('invalidated_at')
            ->where('expires_at', '>', now());
    }

    public function isPending(): bool
    {
        return $this->consumed_at === null
            && $this->invalidated_at === null
            && $this->expires_at->isFuture();
    }

    /**
     * Expired tokens are kept for a month, then removed by model:prune (docs/cron.md).
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<', now()->subDays(30));
    }
}
