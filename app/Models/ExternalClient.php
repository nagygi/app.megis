<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Database\Factories\ExternalClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A client the organization serves (35). Its users tell how they experience
 * the organization's values and processes; they are not members of it and do
 * not count in the headcount.
 */
#[Fillable(['organization_id', 'name', 'description', 'deactivated_at'])]
class ExternalClient extends Model
{
    /** @use HasFactory<ExternalClientFactory> */
    use Blameable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return HasMany<ExternalClientItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ExternalClientItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * The diagnoses the client is asked in (35 · 35.4).
     *
     * @return BelongsToMany<MainDiagnosis, $this>
     */
    public function mainDiagnoses(): BelongsToMany
    {
        return $this->belongsToMany(MainDiagnosis::class)->withPivot('invited_at')->withTimestamps();
    }

    /**
     * @return HasMany<ExternalClientAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ExternalClientAnswer::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }
}
