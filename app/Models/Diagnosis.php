<?php

namespace App\Models;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\DiagnosisStatus;
use App\Models\Concerns\Blameable;
use Carbon\CarbonInterface;
use Database\Factories\DiagnosisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A recorded diagnosis of an organization, team or user (04).
 */
#[Fillable([
    'name', 'description', 'organization_id', 'level', 'team_id', 'user_id',
    'diagnosis_type_id', 'diagnosed_on', 'diagnosed_until', 'creator_kind', 'status', 'aggregation', 'material_id', 'speaker',
])]
class Diagnosis extends Model
{
    /** @use HasFactory<DiagnosisFactory> */
    use Blameable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => DiagnosisLevel::class,
            'creator_kind' => DiagnosisCreator::class,
            'status' => DiagnosisStatus::class,
            'diagnosed_on' => 'date',
            'diagnosed_until' => 'date',
            'aggregation' => 'array',
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
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The source file of an automatic diagnosis (06 · 6.3, 6.5).
     *
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<DiagnosisType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DiagnosisType::class, 'diagnosis_type_id');
    }

    /**
     * @return HasMany<DiagnosisRow, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(DiagnosisRow::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<DiagnosisRow, $this>
     */
    public function valueRows(): HasMany
    {
        return $this->rows()->where('kind', DiagnosisRowKind::Value);
    }

    /**
     * @return HasMany<DiagnosisRow, $this>
     */
    public function treeRows(): HasMany
    {
        return $this->rows()->where('kind', DiagnosisRowKind::TreeElement);
    }

    /**
     * @return HasMany<DiagnosisTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(DiagnosisTransition::class);
    }

    /**
     * The date of the diagnosis: the end of the period when there is one.
     */
    public function referenceDate(): CarbonInterface
    {
        return $this->diagnosed_until ?? $this->diagnosed_on;
    }

    /**
     * "Csapat · Értékesítés", "Felhasználói · Kiss Anna" (04 · 4.2).
     */
    public function subjectLabel(): string
    {
        return match ($this->level) {
            DiagnosisLevel::Team => $this->level->label().' · '.$this->team?->name,
            DiagnosisLevel::User => $this->level->label().' · '.$this->user?->name,
            default => $this->level->label(),
        };
    }

    public function dateLabel(): string
    {
        return $this->diagnosed_until === null
            ? $this->diagnosed_on->format('Y.m.d.')
            : $this->diagnosed_on->format('Y.m.d.').' – '.$this->diagnosed_until->format('Y.m.d.');
    }
}
