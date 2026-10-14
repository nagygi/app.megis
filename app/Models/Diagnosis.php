<?php

namespace App\Models;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\DiagnosisStatus;
use App\Enums\ScoreState;
use App\Models\Concerns\Blameable;
use Carbon\CarbonInterface;
use Database\Factories\DiagnosisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A recorded diagnosis of an organization, team or user (04).
 */
#[Fillable([
    'name', 'description', 'organization_id', 'level', 'team_id', 'user_id',
    'diagnosis_type_id', 'diagnosed_on', 'diagnosed_until', 'creator_kind', 'status', 'aggregation', 'material_id', 'speaker',
    'parent_id', 'value_set_version_id', 'excluded_at', 'process_id',
    'score', 'score_state', 'automatic_score', 'automatic_score_state', 'score_override_reason', 'score_overridden_by', 'score_overridden_at',
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
            'excluded_at' => 'datetime',
            'score' => 'float',
            'score_state' => ScoreState::class,
            'automatic_score' => 'float',
            'automatic_score_state' => ScoreState::class,
            'score_overridden_at' => 'datetime',
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
     * The main diagnosis this one belongs to (17 · 17.4).
     *
     * @return BelongsTo<Diagnosis, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class, 'parent_id');
    }

    /**
     * @return HasMany<Diagnosis, $this>
     */
    public function subdiagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'parent_id');
    }

    /**
     * What a main diagnosis needs to run (17); null for every other diagnosis.
     *
     * @return HasOne<MainDiagnosis, $this>
     */
    public function main(): HasOne
    {
        return $this->hasOne(MainDiagnosis::class);
    }

    /**
     * The organization's value set version a Megélés diagnosis answered (08 · 8.4).
     *
     * @return BelongsTo<Version, $this>
     */
    public function valueSetVersion(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'value_set_version_id');
    }

    /**
     * The process of a Folyamat level diagnosis (13 · 13.1).
     *
     * @return BelongsTo<Process, $this>
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * @return HasMany<DiagnosisClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(DiagnosisClaim::class)->orderBy('id');
    }

    /**
     * @return HasMany<DiagnosisText, $this>
     */
    public function texts(): HasMany
    {
        return $this->hasMany(DiagnosisText::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function scoreOverrider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'score_overridden_by');
    }

    /**
     * Calculated from the materials until publishing (13, 18): the main
     * diagnosis and its process diagnoses. Rows are written over with a reason.
     */
    public function isCalculated(): bool
    {
        return $this->main !== null || ($this->level === DiagnosisLevel::Process && $this->parent_id !== null);
    }

    /**
     * "4,3", "–" (no trace) or empty: the point of a process diagnosis (13 · 13.1).
     */
    public function scoreLabel(): string
    {
        return match (true) {
            $this->score_state === ScoreState::NoTrace, $this->score_state === ScoreState::InsufficientData => '–',
            $this->score === null => '',
            default => number_format($this->score, 1, ',', ''),
        };
    }

    public function isSubdiagnosis(): bool
    {
        return $this->parent_id !== null;
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
            DiagnosisLevel::Process => $this->level->label().' · '.$this->process?->name,
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
