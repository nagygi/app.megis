<?php

namespace App\Models;

use App\Enums\MirrorInsightStatus;
use App\Enums\MirrorStatus;
use Database\Factories\MirrorSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One Értéktükör run (06 · 6.1, 6.2). Anonymous runs expire after 24 hours;
 * a confirmed account claims the run and its diagnosis.
 */
#[Fillable([
    'user_id', 'organization_id', 'material_id', 'diagnosis_id', 'status', 'insight_status', 'email',
    'accepted_legal_versions', 'accepted_at', 'confirmation_sent_at', 'curiosity', 'booking_requested_at', 'claimed_at', 'expires_at',
])]
class MirrorSession extends Model
{
    /** @use HasFactory<MirrorSessionFactory> */
    use HasFactory;

    /**
     * The four curiosity questions of ET5 (06 · 6.1); the labels are in lang.
     */
    public const CURIOSITY = ['inner', 'outer', 'gap', 'capabilities'];

    /**
     * The elements of the tree each curiosity question asks about (ET5).
     */
    public const CURIOSITY_SYMBOLS = [
        'inner' => ['root', 'trunk'],
        'outer' => ['fruit', 'shade'],
        'gap' => ['mycelium'],
        'capabilities' => ['fire'],
    ];

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
            'insight_status' => MirrorInsightStatus::class,
            'accepted_legal_versions' => 'array',
            'accepted_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
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

    /**
     * @return HasMany<MirrorValueInsight, $this>
     */
    public function insights(): HasMany
    {
        return $this->hasMany(MirrorValueInsight::class);
    }

    public function isAnonymous(): bool
    {
        return $this->claimed_at === null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * The e-mail came with the mirror and the link went out; the account waits for it.
     */
    public function awaitsConfirmation(): bool
    {
        return $this->isAnonymous() && $this->confirmation_sent_at !== null;
    }

    /**
     * The e-mail belongs to an account already: it signs in on ET5 instead.
     */
    public function belongsToExistingAccount(): bool
    {
        return $this->isAnonymous() && $this->email !== null && $this->confirmation_sent_at === null;
    }

    /**
     * Whose the mirror is, for the admin list (Értékmérések).
     *
     * @return 'claimed'|'awaiting_confirmation'|'awaiting_login'|'anonymous'
     */
    public function accountState(): string
    {
        return match (true) {
            ! $this->isAnonymous() => 'claimed',
            $this->awaitsConfirmation() => 'awaiting_confirmation',
            $this->belongsToExistingAccount() => 'awaiting_login',
            default => 'anonymous',
        };
    }
}
