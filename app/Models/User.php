<?php

namespace App\Models;

use App\Enums\AdminStatus;
use App\Enums\MembershipStatus;
use App\Enums\TokenPurpose;
use App\Enums\UserRole;
use App\Models\Concerns\Blameable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'role'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Blameable, HasFactory, Notifiable;

    /**
     * Mirrors the database defaults, so a freshly created model compares correctly.
     *
     * @var array<string, int>
     */
    protected $attributes = [
        'failed_login_attempts' => 0,
        'failed_password_attempts' => 0,
        'auth_epoch' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'locked_at' => 'datetime',
            'password_paused_until' => 'datetime',
            'last_login_at' => 'datetime',
            'mail_undeliverable_at' => 'datetime',
            'failed_login_attempts' => 'integer',
            'failed_password_attempts' => 'integer',
            'auth_epoch' => 'integer',
            'deletion_requested_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    /**
     * E-mail addresses are compared case-insensitively, so they are stored lower-case.
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => Str::lower(trim($value)));
    }

    /**
     * Hungarian order: family name first.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(get: function (): string {
            // A deleted account keeps its row as a tombstone (31 · 31.7, 33 · 33.7).
            if ($this->anonymized_at !== null) {
                return __('client.deleted_user', ['id' => $this->id]);
            }

            $name = trim(($this->last_name ?? '').' '.($this->first_name ?? ''));

            return $name !== '' ? $name : $this->email;
        });
    }

    /**
     * Monogram for the avatar (15 · 15.2).
     *
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::make(get: function (): string {
            $initials = collect([$this->last_name, $this->first_name])
                ->filter()
                ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
                ->implode('');

            return $initials !== '' ? $initials : Str::upper(Str::substr($this->email, 0, 1));
        });
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return BelongsToMany<Organization, $this, Membership>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(Membership::class)
            ->withPivot(['id', 'status', 'position', 'is_organization_leader', 'is_employee', 'is_partner'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot('is_leader')->withTimestamps();
    }

    /**
     * @return HasMany<UserToken, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(UserToken::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function lastOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'last_organization_id');
    }

    /**
     * Organizations the user may currently enter: active membership in an active organization.
     *
     * @return BelongsToMany<Organization, $this, Membership>
     */
    public function accessibleOrganizations(): BelongsToMany
    {
        return $this->organizations()
            ->wherePivot('status', MembershipStatus::Active->value)
            ->whereNull('organizations.deactivated_at');
    }

    #[Scope]
    protected function admins(Builder $query): void
    {
        $query->whereNotNull('role');
    }

    public function isAdmin(): bool
    {
        return $this->role !== null;
    }

    public function isSuperadmin(): bool
    {
        return $this->role === UserRole::Superadmin;
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    public function isActivated(): bool
    {
        return $this->activated_at !== null;
    }

    public function isPasswordPaused(): bool
    {
        return $this->password_paused_until !== null && $this->password_paused_until->isFuture();
    }

    /**
     * Uses the "has_pending_admin_invitation" attribute when the query added it,
     * so the admin list does not query per row.
     */
    public function adminStatus(): AdminStatus
    {
        if ($this->deactivated_at !== null) {
            return AdminStatus::Inactive;
        }

        if ($this->locked_at !== null) {
            return AdminStatus::Locked;
        }

        if ($this->activated_at !== null) {
            return AdminStatus::Active;
        }

        $hasPendingInvitation = $this->has_pending_admin_invitation
            ?? $this->tokens()->pending()->whereIn('purpose', [TokenPurpose::AdminInvitation, TokenPurpose::AdminPasswordReset])->exists();

        return $hasPendingInvitation ? AdminStatus::Invited : AdminStatus::InvitationExpired;
    }

    /**
     * Status on the customer list (33 · 33.8): "active" with any usable
     * membership, "pending" until registration, otherwise "inactive".
     * Uses the loaded memberships.
     */
    public function clientStatus(): string
    {
        if ($this->deactivated_at !== null) {
            return 'inactive';
        }

        if ($this->activated_at === null) {
            return 'pending';
        }

        return $this->memberships->contains(fn (Membership $membership): bool => $membership->status !== MembershipStatus::Inactive)
            ? 'active'
            : 'inactive';
    }

    /**
     * Ends every other session of this user (password change, reset, deactivation).
     */
    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    public function invalidateSessions(): void
    {
        $this->increment('auth_epoch');
    }
}
