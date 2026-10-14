<?php

namespace App\Models;

use App\Enums\OrganizationType;
use App\Models\Concerns\Blameable;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['type', 'name', 'slug', 'description', 'contact_admin_id', 'product_id', 'headcount_limit', 'process_recommendation'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use Blameable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'headcount_limit' => 'integer',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function contactAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contact_admin_id');
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasOne<OrganizationValueSet, $this>
     */
    public function valueSet(): HasOne
    {
        return $this->hasOne(OrganizationValueSet::class);
    }

    /**
     * @return HasMany<Process, $this>
     */
    public function processes(): HasMany
    {
        return $this->hasMany(Process::class);
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return BelongsToMany<User, $this, Membership>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(Membership::class)
            ->withPivot(['id', 'status', 'position', 'is_organization_leader', 'is_employee', 'is_partner'])
            ->withTimestamps();
    }

    public function isIndividual(): bool
    {
        return $this->type === OrganizationType::Individual;
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    /**
     * A private person's organization has exactly one user (02 · 2.3).
     */
    public function effectiveHeadcountLimit(): ?int
    {
        return $this->isIndividual() ? 1 : $this->headcount_limit;
    }

    /**
     * Added, invited and active users count, partners included; inactive ones do not.
     */
    public function headcount(): int
    {
        return $this->memberships_counting_count
            ?? $this->memberships()->countingTowardsLimit()->count();
    }

    public function hasFreeSeat(): bool
    {
        $limit = $this->effectiveHeadcountLimit();

        return $limit === null || $this->headcount() < $limit;
    }

    /**
     * "8 / 10" with a limit, "8" without (02 · 2.2).
     */
    public function headcountLabel(): string
    {
        $limit = $this->effectiveHeadcountLimit();

        return $limit === null ? (string) $this->headcount() : $this->headcount().' / '.$limit;
    }
}
