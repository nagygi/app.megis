<?php

namespace App\Models;

use App\Enums\CapabilityLevel;
use App\Enums\CapabilityNature;
use App\Enums\CapabilityStatus;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasVersions;
use Database\Factories\CapabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A capability of the global Képességtár (07). Every saved change and every
 * load with a change is a new version (07 · 7.3).
 */
#[Fillable([
    'code', 'name', 'name_en', 'slug', 'capability_group_id', 'level', 'status', 'nature', 'tempting',
    'definition', 'statement', 'non_examples', 'overlaps', 'appearances', 'stages', 'keywords',
    'details', 'proposed_fields', 'field_states', 'imported_at', 'archived_at',
])]
class Capability extends Model
{
    /** @use HasFactory<CapabilityFactory> */
    use Blameable, HasFactory, HasVersions;

    /**
     * The list fields of the card, in the order of the form (07 · 7.2).
     */
    public const LISTS = ['non_examples', 'overlaps', 'keywords'];

    /**
     * The six steps, Csak szavak … Továbbadjuk (07 · 7.2).
     */
    public const STAGES = [1, 2, 3, 4, 5, 6];

    /**
     * The three empty values of the Sheet besides a filled field
     * (kepessegrendszer_osszefoglalo.md · 7).
     */
    public const FIELD_STATES = ['not_yet', 'not_applicable', 'insufficient_evidence'];

    /**
     * Longest list item: the mégis texts run to several sentences (07 · 7.2).
     */
    public const ITEM_MAX_LENGTH = 1000;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'non_examples' => '[]',
        'overlaps' => '[]',
        'appearances' => '{}',
        'stages' => '{}',
        'keywords' => '[]',
        'details' => '[]',
        'proposed_fields' => '[]',
        'field_states' => '{}',
        'search_text' => '',
    ];

    protected static function booted(): void
    {
        static::saving(function (Capability $capability): void {
            $capability->search_text = mb_strtolower(implode("\n", array_filter([
                $capability->code,
                $capability->name,
                $capability->name_en,
                ...$capability->keywords,
            ])));
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => CapabilityLevel::class,
            'status' => CapabilityStatus::class,
            'nature' => CapabilityNature::class,
            'tempting' => 'boolean',
            'non_examples' => 'array',
            'overlaps' => 'array',
            'appearances' => 'array',
            'stages' => 'array',
            'keywords' => 'array',
            'details' => 'array',
            'proposed_fields' => 'array',
            'field_states' => 'array',
            'imported_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CapabilityGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CapabilityGroup::class, 'capability_group_id');
    }

    /**
     * @return BelongsToMany<Precondition, $this>
     */
    public function preconditions(): BelongsToMany
    {
        return $this->belongsToMany(Precondition::class)
            ->withPivot(['note', 'position'])
            ->orderByPivot('position');
    }

    /**
     * Capabilities this one needs ("képesség-függőség", 07 · 7.2).
     *
     * @return BelongsToMany<Capability, $this>
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'capability_dependencies', 'capability_id', 'depends_on_id')
            ->withPivot(['note', 'position'])
            ->orderByPivot('position');
    }

    /**
     * The patterns of the Mintatár that recognise it, with the fit (NM-19).
     *
     * @return BelongsToMany<Pattern, $this>
     */
    public function patterns(): BelongsToMany
    {
        return $this->belongsToMany(Pattern::class)->withPivot(['fit', 'fit_note', 'position']);
    }

    /**
     * Not archived (07 · 7.5).
     *
     * @param  Builder<Capability>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isProposed(string $field): bool
    {
        return in_array($field, $this->proposed_fields, true);
    }

    /**
     * "nincs még", "nem értelmezhető" or "nincs elég külső bizonyíték", when the Sheet said so.
     */
    public function fieldState(string $field): ?string
    {
        return $this->field_states[$field] ?? null;
    }

    public function versionSnapshot(): array
    {
        $group = $this->capability_group_id === null ? null : $this->group()->first();

        return [
            'code' => $this->code,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'group' => $group === null ? null : ['id' => $group->id, 'name' => $group->name],
            'level' => $this->level?->value,
            'status' => $this->status?->value,
            'nature' => $this->nature?->value,
            'tempting' => $this->tempting,
            'definition' => $this->definition,
            'statement' => $this->statement,
            'non_examples' => array_values($this->non_examples),
            'overlaps' => array_values($this->overlaps),
            ...collect(CapabilityLevel::cases())->mapWithKeys(fn (CapabilityLevel $level): array => [
                'appearance_'.$level->value => $this->appearances[$level->value] ?? null,
            ])->all(),
            'preconditions' => $this->preconditions()->get()
                ->map(fn (Precondition $precondition): string => self::linkLabel($precondition->code, $precondition->pivot->note))
                ->all(),
            'dependencies' => $this->dependencies()->get()
                ->map(fn (Capability $dependency): string => self::linkLabel($dependency->code, $dependency->pivot->note))
                ->all(),
            ...collect(self::STAGES)->mapWithKeys(fn (int $stage): array => [
                'stage_'.$stage => $this->stages[(string) $stage] ?? null,
            ])->all(),
            'keywords' => array_values($this->keywords),
            'details' => collect($this->details)->map(fn (array $detail): string => $detail['label'].': '.$detail['value'])->all(),
        ];
    }

    private static function linkLabel(string $code, ?string $note): string
    {
        return filled($note) ? $code.' · '.$note : $code;
    }
}
