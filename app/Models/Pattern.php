<?php

namespace App\Models;

use App\Enums\CapabilityLevel;
use App\Enums\LevelMarker;
use App\Enums\PatternMaturity;
use App\Enums\PatternOrigin;
use App\Enums\PatternProduct;
use App\Enums\PatternSubtype;
use App\Enums\PatternType;
use App\Enums\PatternValidity;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasVersions;
use Database\Factories\PatternFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A pattern of the Mintatár (NM-19): what a capability, a precondition or a
 * detector error looks like in real material. The mégis Sheet is the master,
 * so the admin only reads it; every load with a change is a new version.
 */
#[Fillable([
    'code', 'type', 'subtype', 'name', 'level', 'level_marker', 'externally_visible', 'products',
    'validity', 'origin', 'content', 'where_visible', 'precondition_note', 'proposed_fields',
    'field_states', 'imported_at', 'archived_at',
])]
class Pattern extends Model
{
    /** @use HasFactory<PatternFactory> */
    use Blameable, HasFactory, HasVersions;

    /**
     * The text fields of the Sheet, by block of the pattern page (6.6).
     * "conditions" (feltételek) and the source block are for the mégis only.
     */
    public const CONTENT_BLOCKS = [
        'recognition' => ['situation', 'behavior', 'mechanism', 'conditions', 'minimal_trace'],
        'boundary' => ['appearance', 'consequence', 'non_examples', 'alternative_explanation', 'counter_test'],
        'statement' => ['statement', 'next_question', 'remedy'],
        'marks' => ['marker_note', 'externally_visible_note', 'ecosystem_direction'],
        'source' => ['external_source', 'source_level', 'strength', 'icp_transferability', 'reference_check'],
        'provenance' => ['recorded_by', 'recorded_on', 'approved_by', 'reviewed_on'],
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'products' => '[]',
        'content' => '{}',
        'where_visible' => '[]',
        'proposed_fields' => '[]',
        'field_states' => '{}',
        'search_text' => '',
    ];

    protected static function booted(): void
    {
        static::saving(function (Pattern $pattern): void {
            $pattern->search_text = mb_strtolower(implode("\n", array_filter([
                $pattern->code,
                $pattern->name,
                $pattern->content['behavior'] ?? null,
            ])));
        });
    }

    /**
     * @return list<string>
     */
    public static function contentFields(): array
    {
        return array_merge(...array_values(self::CONTENT_BLOCKS));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PatternType::class,
            'subtype' => PatternSubtype::class,
            'level' => CapabilityLevel::class,
            'level_marker' => LevelMarker::class,
            'externally_visible' => 'boolean',
            'products' => 'array',
            'validity' => PatternValidity::class,
            'origin' => PatternOrigin::class,
            'content' => 'array',
            'where_visible' => 'array',
            'proposed_fields' => 'array',
            'field_states' => 'array',
            'sheet_maturity' => PatternMaturity::class,
            'imported_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * The linked capabilities with the fit (pontos / részleges).
     *
     * @return BelongsToMany<Capability, $this>
     */
    public function capabilities(): BelongsToMany
    {
        return $this->belongsToMany(Capability::class)
            ->withPivot(['fit', 'fit_note', 'position'])
            ->orderByPivot('position');
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
     * Valid and not archived: the patterns the recognition may use.
     *
     * @param  Builder<Pattern>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at')->where('validity', PatternValidity::Valid);
    }

    /**
     * The maturity by the formula of the Sheet, from the clients with an
     * approved finding in the mégis tár.
     */
    public function maturity(): PatternMaturity
    {
        return PatternMaturity::compute($this->validity, $this->sheet_approved_clients, $this->origin);
    }

    public function text(string $field): ?string
    {
        $text = $this->content[$field] ?? null;

        return filled($text) ? $text : null;
    }

    public function hasProduct(PatternProduct $product): bool
    {
        return in_array($product->value, $this->products, true);
    }

    public function isProposed(string $field): bool
    {
        return in_array($field, $this->proposed_fields, true);
    }

    public function fieldState(string $field): ?string
    {
        return $this->field_states[$field] ?? null;
    }

    public function versionSnapshot(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type?->value,
            'subtype' => $this->subtype?->value,
            'level' => $this->level?->value,
            'level_marker' => $this->level_marker?->value,
            'externally_visible' => $this->externally_visible,
            'products' => array_values($this->products),
            'validity' => $this->validity?->value,
            'origin' => $this->origin?->value,
            'capabilities' => $this->capabilities()->get()
                ->map(fn (Capability $capability): string => self::linkLabel($capability->code, collect([
                    $capability->pivot->fit === null ? null : __('enums.pattern_fit.'.$capability->pivot->fit),
                    $capability->pivot->fit_note,
                ])->filter()->implode(', ')))
                ->all(),
            'preconditions' => $this->preconditions()->get()
                ->map(fn (Precondition $precondition): string => self::linkLabel($precondition->code, $precondition->pivot->note))
                ->all(),
            'precondition_note' => $this->precondition_note,
            'where_visible' => array_values($this->where_visible),
            ...collect(self::contentFields())->mapWithKeys(fn (string $field): array => [$field => $this->content[$field] ?? null])->all(),
        ];
    }

    private static function linkLabel(string $code, ?string $note): string
    {
        return filled($note) ? $code.' · '.$note : $code;
    }
}
