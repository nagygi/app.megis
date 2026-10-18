<?php

namespace App\Models;

use App\Enums\ValueGrade;
use App\Enums\ValueOrigin;
use App\Enums\ValueSvsStatus;
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
    'code', 'name', 'name_en', 'slug', 'value_category_id', 'definition', 'definition_en',
    'meanings', 'non_meanings', 'false_positives', 'overlaps', 'high_examples', 'low_examples', 'examples', 'keywords', 'literature',
    'evidence', 'transposability', 'svs_status',
    'origin', 'origin_organization_id', 'origin_user_id', 'reviewed_at', 'archived_at',
])]
class Value extends Model
{
    /** @use HasFactory<ValueFactory> */
    use Blameable, HasFactory, HasVersions;

    /**
     * The list fields of the value card, in the order of the form (03 · 3.2).
     */
    public const LISTS = ['meanings', 'non_meanings', 'false_positives', 'overlaps', 'high_examples', 'low_examples', 'examples', 'keywords', 'literature'];

    public const ITEM_MAX_LENGTH = 255;

    /**
     * A literature item names the source and the measure, so it runs longer.
     */
    public const LITERATURE_ITEM_MAX_LENGTH = 500;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'meanings' => '[]',
        'non_meanings' => '[]',
        'false_positives' => '[]',
        'overlaps' => '[]',
        'high_examples' => '[]',
        'low_examples' => '[]',
        'examples' => '[]',
        'keywords' => '[]',
        'literature' => '[]',
        'search_text' => '',
    ];

    protected static function booted(): void
    {
        static::saving(function (Value $value): void {
            $value->has_endpoints = $value->high_examples !== [] && $value->low_examples !== [];
            $value->search_text = mb_strtolower(implode("\n", array_filter([$value->name, $value->name_en, ...$value->keywords])));
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
            'false_positives' => 'array',
            'overlaps' => 'array',
            'high_examples' => 'array',
            'low_examples' => 'array',
            'examples' => 'array',
            'keywords' => 'array',
            'literature' => 'array',
            'evidence' => ValueGrade::class,
            'transposability' => ValueGrade::class,
            'svs_status' => ValueSvsStatus::class,
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
     * What a user may choose for their own values (24 · 24.3): reviewed values,
     * the ones they added themselves and the ones of their organization.
     */
    #[Scope]
    protected function availableToUser(Builder $query, User $user, ?Organization $organization = null): void
    {
        $query->whereNull('archived_at')->where(function (Builder $available) use ($user, $organization): void {
            $available->whereNotNull('reviewed_at')->orWhere('origin_user_id', $user->id);

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

    public static function itemMaxLength(string $list): int
    {
        return $list === 'literature' ? self::LITERATURE_ITEM_MAX_LENGTH : self::ITEM_MAX_LENGTH;
    }

    public function versionSnapshot(): array
    {
        $category = $this->value_category_id === null ? null : $this->category()->first();

        return [
            'code' => $this->code,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'category' => $category === null ? null : ['id' => $category->id, 'name' => $category->name],
            'definition' => $this->definition,
            'definition_en' => $this->definition_en,
            ...collect(self::LISTS)->mapWithKeys(fn (string $list): array => [$list => array_values($this->{$list})])->all(),
            'evidence' => $this->evidence?->value,
            'transposability' => $this->transposability?->value,
            'svs_status' => $this->svs_status?->value,
        ];
    }
}
