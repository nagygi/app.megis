<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\TokenPurpose;
use App\Models\Concerns\Blameable;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A user in an organization, with the organization-specific position and tags (02 · 2.5, 2.6).
 */
#[Fillable(['organization_id', 'user_id', 'status', 'position', 'is_organization_leader', 'is_employee', 'is_partner'])]
class Membership extends Pivot
{
    /** @use HasFactory<MembershipFactory> */
    use Blameable, HasFactory;

    protected $table = 'organization_user';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
            'is_organization_leader' => 'boolean',
            'is_employee' => 'boolean',
            'is_partner' => 'boolean',
            'invited_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function countingTowardsLimit(Builder $query): void
    {
        $query->where('status', '!=', MembershipStatus::Inactive->value);
    }

    /**
     * Translated labels of the organization-level tags.
     *
     * @return list<string>
     */
    public function tagLabels(): array
    {
        return array_values(array_filter([
            $this->is_organization_leader ? __('enums.membership_tag.organization_leader') : null,
            $this->is_employee ? __('enums.membership_tag.employee') : null,
            $this->is_partner ? __('enums.membership_tag.partner') : null,
        ]));
    }

    /**
     * The status shown in lists: an invitation whose link expired reads "Meghívó lejárt".
     */
    public function displayStatusLabel(): string
    {
        if ($this->status === MembershipStatus::Invited && ! $this->hasPendingInvitation()) {
            return __('enums.membership_status.invitation_expired');
        }

        return $this->status->label();
    }

    public function hasPendingInvitation(): bool
    {
        return $this->has_pending_invitation
            ?? UserToken::query()
                ->pending()
                ->where('purpose', TokenPurpose::ClientInvitation)
                ->where('user_id', $this->user_id)
                ->where('organization_id', $this->organization_id)
                ->exists();
    }
}
