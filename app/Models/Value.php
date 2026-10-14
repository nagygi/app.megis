<?php

namespace App\Models;

use App\Enums\ValueOrigin;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasVersions;
use Database\Factories\ValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A value of the global Értéktár (03). Every saved change is a new version.
 */
#[Fillable([
    'name', 'slug', 'value_category_id',
    'meanings', 'non_meanings', 'high_examples', 'low_examples', 'examples', 'keywords',
    'origin', 'origin_organization_id', 'reviewed_at', 'archived_at',
])]
class Value extends Model
{
    /** @use HasFactory<ValueFactory> */
    use Blameable, HasFactory, HasVersions;

    /**
     * The list fields of the value card, in the order of the form (03 · 3.2).
     */
    public const LISTS = ['meanings', 'non_meanings', 'high_examples', 'low_examples', 'examples', 'keywords'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'meanings' => '[]',
        'non_meanings' => '[]',
        'high_examples' => '[]',
        'low_examples' => '[]',
        'examples' => '[]',
        'keywords' => '[]',
        'search_text' => '',
    ];

    protected static function booted(): void
    {
        static::saving(function (Value $value): void {
            $value->has_endpoints = $value->high_examples !== [] && $value->low_examples !== [];
            $value->search_text = mb_strtolower(implode("\n", [$value->name, ...$value->keywords]));
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meanings' => 'array',
            'non_meanings' => 'array',
            'high_examples' => 'array',
            'low_examples' => 'array',
            'examples' => 'array',
            'keywords' => 'array',
            'has_endpoints' => 'boolean',
            'origin' => ValueOrigin::class,
            'reviewed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ValueCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ValueCategory::class, 'value_category_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function originOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'origin_organization_id');
    }

    /**
     * Not archived (03 · 3.5).
     *
     * @param  Builder<Value>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Selectable in a new place of the organization: reviewed, or created
     * there and not reviewed yet (03 · 3.7).
     *
     * @param  Builder<Value>  $query
     */
    #[Scope]
    protected function availableTo(Builder $query, ?Organization $organization): void
    {
        $query->whereNull('archived_at')->where(function (Builder $available) use ($organization): void {
            $available->whereNotNull('reviewed_at');

            if ($organization !== null) {
                $available->orWhere('origin_organization_id', $organization->id);
            }
        });
    }

    /**
     * Came from outside the admin form and nobody has reviewed it yet (03 · 3.7).
     *
     * @param  Builder<Value>  $query
     */
    #[Scope]
    protected function awaitingReview(Builder $query): void
    {
        $query->whereNull('reviewed_at')->where('origin', '!=', ValueOrigin::Admin);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }

    public function versionSnapshot(): array
    {
        $category = $this->value_category_id === null ? null : $this->category()->first();

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $category === null ? null : ['id' => $category->id, 'name' => $category->name],
            ...collect(self::LISTS)->mapWithKeys(fn (string $list): array => [$list => array_values($this->{$list})])->all(),
        ];
    }
}
