<?php

namespace App\Models;

use App\Enums\MirrorStatus;
use Database\Factories\MirrorSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One Értéktükör run (06 · 6.1, 6.2). Anonymous runs expire after 24 hours;
 * a confirmed account claims the run and its diagnosis.
 */
#[Fillable([
    'user_id', 'organization_id', 'material_id', 'diagnosis_id', 'status', 'email',
    'accepted_legal_versions', 'accepted_at', 'curiosity', 'booking_requested_at', 'claimed_at', 'expires_at',
])]
class MirrorSession extends Model
{
    /** @use HasFactory<MirrorSessionFactory> */
    use HasFactory;

    /**
     * The four curiosity questions of ET5 (06 · 6.1); the labels are in lang.
     */
    public const CURIOSITY = ['inner', 'outer', 'gap', 'capabilities'];

    protected static function booted(): void
    {
        static::creating(function (MirrorSession $session): void {
            $session->token ??= Str::random(48);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MirrorStatus::class,
            'accepted_legal_versions' => 'array',
            'accepted_at' => 'datetime',
            'curiosity' => 'array',
            'booking_requested_at' => 'datetime',
            'claimed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<Diagnosis, $this>
     */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function isAnonymous(): bool
    {
        return $this->claimed_at === null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
