<?php

namespace App\Models;

use App\Enums\DiagnosisRowKind;
use App\Enums\ExperienceState;
use App\Enums\ScoreState;
use App\Enums\TreeElement;
use Database\Factories\DiagnosisRowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A value or brand base row of a diagnosis with its score and self-reported
 * experience (04 · 4.4).
 */
#[Fillable([
    'kind', 'value_id', 'value_version_id', 'tree_element', 'position',
    'score', 'score_state', 'experience', 'experience_state',
    'experience_met_example', 'experience_unmet_example', 'reasoning', 'quotes',
    'assigned_on', 'aggregation_stats', 'manually_modified',
    'process_id', 'area_id', 'automatic_score', 'automatic_score_state', 'override_reason', 'overridden_by', 'overridden_at',
])]
class DiagnosisRow extends Model
{
    /** @use HasFactory<DiagnosisRowFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => DiagnosisRowKind::class,
            'tree_element' => TreeElement::class,
            'score' => 'float',
            'score_state' => ScoreState::class,
            'experience' => 'float',
            'experience_state' => ExperienceState::class,
            'assigned_on' => 'date',
            'aggregation_stats' => 'array',
            'quotes' => 'array',
            'manually_modified' => 'boolean',
            'automatic_score' => 'float',
            'automatic_score_state' => ScoreState::class,
            'overridden_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Diagnosis, $this>
     */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * @return BelongsTo<Value, $this>
     */
    public function value(): BelongsTo
    {
        return $this->belongsTo(Value::class);
    }

    /**
     * @return BelongsTo<Version, $this>
     */
    public function valueVersion(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'value_version_id');
    }

    /**
     * @return BelongsTo<Process, $this>
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * @return HasMany<EvidenceTrace, $this>
     */
    public function traces(): HasMany
    {
        return $this->hasMany(EvidenceTrace::class)->orderBy('recorded_on')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function overrider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    public function label(): string
    {
        return match ($this->kind) {
            DiagnosisRowKind::Value => (string) $this->value?->name,
            DiagnosisRowKind::Process => (string) $this->process?->name,
            DiagnosisRowKind::Area => (string) $this->area?->name,
            default => (string) $this->tree_element?->label(),
        };
    }

    /**
     * A row written by hand whose calculated value differs (13 · 13.4).
     */
    public function differsFromAutomatic(): bool
    {
        return $this->overridden_at !== null
            && ($this->automatic_score !== $this->score || $this->automatic_score_state !== $this->score_state);
    }

    /**
     * "4", "4,3" (aggregated), "–" (no trace) or empty.
     */
    public function scoreLabel(): string
    {
        return match (true) {
            $this->score_state === ScoreState::NoTrace => '–',
            $this->score === null => '',
            default => $this->formatNumber($this->score),
        };
    }

    public function experienceLabel(): string
    {
        return match (true) {
            $this->experience_state === ExperienceState::DontKnow => __('enums.experience_state.dont_know'),
            $this->experience === null => '',
            default => $this->formatNumber($this->experience),
        };
    }

    /**
     * The gap: how differently they see it than they show it (04, R2·b).
     */
    public function gap(): ?float
    {
        if ($this->score === null || $this->experience === null || $this->score_state !== ScoreState::Scored) {
            return null;
        }

        return round($this->experience - $this->score, 1);
    }

    public function gapLabel(): string
    {
        $gap = $this->gap();

        return $gap === null ? '' : ($gap > 0 ? '+' : '').$this->formatNumber($gap);
    }

    public function isAggregated(): bool
    {
        return $this->aggregation_stats !== null;
    }

    private function formatNumber(float $number): string
    {
        return $this->isAggregated() || fmod($number, 1.0) !== 0.0
            ? number_format($number, 1, ',', '')
            : (string) (int) $number;
    }
}
