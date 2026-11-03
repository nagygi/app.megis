<?php

namespace App\Services\Diagnoses;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\DiagnosisStatus;
use App\Enums\ExperienceState;
use App\Enums\MainDiagnosisStage;
use App\Enums\ScoreState;
use App\Enums\TreeElement;
use App\Enums\ValueOrigin;
use App\Exceptions\DiagnosisException;
use App\Jobs\RecalculateMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\OrganizationValueSet;
use App\Models\User;
use App\Models\Value;
use App\Services\Values\ValueService;
use Illuminate\Support\Facades\DB;

/**
 * Recording diagnoses (04): the four brand base rows, value rows bound to
 * the Értéktár version in use, and status changes with their log.
 */
class DiagnosisService
{
    /**
     * Score input meaning "nincs nyoma" (04).
     */
    public const NO_TRACE = '-';

    /**
     * Experience input meaning "nem tudom" (04 · 4.4).
     */
    public const DONT_KNOW = 'dont_know';

    public function __construct(private ValueService $values) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, DiagnosisCreator $creator = DiagnosisCreator::Admin, DiagnosisStatus $status = DiagnosisStatus::Draft): Diagnosis
    {
        return DB::transaction(function () use ($attributes, $creator, $status): Diagnosis {
            $diagnosis = Diagnosis::create([...$attributes, 'creator_kind' => $creator, 'status' => $status]);

            // A process diagnosis has no brand base block (13 · 13.1).
            if ($diagnosis->level !== DiagnosisLevel::Process) {
                $this->addTreeRows($diagnosis);
            }

            // The calculation writes the rows of its own diagnoses.
            if ($diagnosis->level === DiagnosisLevel::Process && $creator !== DiagnosisCreator::Automatic) {
                $this->addOrganizationValues($diagnosis);
            }

            $this->logTransition($diagnosis, null, $status);

            return $diagnosis;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Diagnosis $diagnosis, array $attributes): void
    {
        $this->ensureEditable($diagnosis);

        DB::transaction(function () use ($diagnosis, $attributes): void {
            $from = $diagnosis->level;
            $diagnosis->update($attributes);

            if ($from === $diagnosis->level) {
                return;
            }

            // The brand base block goes with the Folyamat level, the organization's values come (13 · 13.1).
            if ($diagnosis->level === DiagnosisLevel::Process) {
                $diagnosis->treeRows()->delete();
                $this->addOrganizationValues($diagnosis);
                $this->refreshManualPoint($diagnosis);
            } elseif ($from === DiagnosisLevel::Process) {
                $this->addTreeRows($diagnosis);
            }
        });
    }

    private function addTreeRows(Diagnosis $diagnosis): void
    {
        foreach (TreeElement::cases() as $position => $element) {
            $diagnosis->rows()->firstOrCreate(
                ['kind' => DiagnosisRowKind::TreeElement, 'tree_element' => $element],
                ['position' => $position, 'assigned_on' => $diagnosis->diagnosed_on],
            );
        }
    }

    /**
     * The rows of a Folyamat diagnosis are the organization's values, in the
     * version in force, recorded with the diagnosis (13 · 13.1, 08 · 8.4).
     */
    private function addOrganizationValues(Diagnosis $diagnosis): void
    {
        $set = OrganizationValueSet::query()->where('organization_id', $diagnosis->organization_id)->first();
        $version = $set === null ? null : ($set->latestVersion ?? $set->recordVersion());

        if ($version === null) {
            return;
        }

        $diagnosis->update(['value_set_version_id' => $version->id]);

        foreach ($version->snapshot['values'] ?? [] as $position => $item) {
            $diagnosis->rows()->firstOrCreate(
                ['kind' => DiagnosisRowKind::Value, 'value_id' => $item['value_id']],
                ['value_version_id' => $item['source_version_id'] ?? null, 'position' => $position, 'assigned_on' => $diagnosis->diagnosed_on],
            );
        }
    }

    public function transition(Diagnosis $diagnosis, DiagnosisStatus $to): void
    {
        // A main diagnosis moves by its stage, a subdiagnosis follows it (17 · 17.4, 17.5).
        if ($diagnosis->parent_id !== null || $diagnosis->main()->exists()) {
            throw new DiagnosisException(__('admin.diagnoses.status_follows_stage'));
        }

        $from = $diagnosis->status;

        if (! $from->canTransitionTo($to)) {
            throw new DiagnosisException(__('admin.diagnoses.transition_not_allowed', ['from' => $from->label(), 'to' => $to->label()]));
        }

        if ($to !== DiagnosisStatus::Draft) {
            $this->ensureScored($diagnosis);
        }

        DB::transaction(function () use ($diagnosis, $from, $to): void {
            $diagnosis->update(['status' => $to]);
            $this->logTransition($diagnosis, $from, $to);
        });
    }

    /**
     * "A mégis javaslata" at the end of a standalone diagnosis' report
     * (04 · 4.6); an admin writes it, so it is approved as it is saved.
     */
    public function saveRecommendation(Diagnosis $diagnosis, ?string $text, User $admin): void
    {
        $this->ensureEditable($diagnosis);

        // A main diagnosis writes it in its reading (20 · 20.4).
        if ($diagnosis->parent_id !== null || $diagnosis->main()->exists()) {
            throw new DiagnosisException(__('admin.diagnoses.recommendation.in_reading'));
        }

        $text = trim((string) $text);

        if ($text === '') {
            DiagnosisText::query()->where('diagnosis_id', $diagnosis->id)->where('key', 'recommendation')->delete();

            return;
        }

        DiagnosisText::query()->updateOrCreate(
            ['diagnosis_id' => $diagnosis->id, 'key' => 'recommendation'],
            ['text' => $text, 'meta' => null, 'status' => 'approved', 'ai_call_id' => null, 'approved_at' => now(), 'approved_by' => $admin->id],
        );
    }

    /**
     * Binds the value with its current Értéktár version (04 · 4.4, 03 · 3.3).
     */
    public function addValue(Diagnosis $diagnosis, Value $value): DiagnosisRow
    {
        $this->ensureEditable($diagnosis);

        // The rows of a calculated diagnosis come from the value set, not from "+ Érték" (18 · 18.1).
        if ($diagnosis->isCalculated()) {
            throw new DiagnosisException(__('admin.diagnoses.calculated_values'));
        }

        if (! Value::query()->availableTo($diagnosis->organization)->whereKey($value->id)->exists()) {
            throw new DiagnosisException(__('admin.diagnoses.value_not_available', ['name' => $value->name]));
        }

        if ($diagnosis->valueRows()->where('value_id', $value->id)->exists()) {
            throw new DiagnosisException(__('admin.diagnoses.value_already_added', ['name' => $value->name]));
        }

        $version = $value->latestVersion ?? $value->recordVersion();

        return $diagnosis->rows()->create([
            'kind' => DiagnosisRowKind::Value,
            'value_id' => $value->id,
            'value_version_id' => $version?->id,
            'position' => (int) $diagnosis->valueRows()->max('position') + 1,
            'assigned_on' => $diagnosis->diagnosed_on,
        ]);
    }

    /**
     * A value typed by name: the Értéktár value when there is one, otherwise a
     * new "Diagnózisnál felvett" value (04 · 4.4, 03 · 3.7).
     */
    public function addValueByName(Diagnosis $diagnosis, string $name): DiagnosisRow
    {
        $this->ensureEditable($diagnosis);

        return DB::transaction(function () use ($diagnosis, $name): DiagnosisRow {
            $value = $this->values->findByName($name)
                ?? $this->values->createElsewhere($name, ValueOrigin::Diagnosis, $diagnosis->organization);

            return $this->addValue($diagnosis, $value);
        });
    }

    /**
     * @param  array{score?: ?string, experience?: ?string, experience_met_example?: ?string, experience_unmet_example?: ?string, reasoning?: ?string, assigned_on?: string}  $input
     */
    public function updateRow(DiagnosisRow $row, array $input): void
    {
        $this->ensureEditable($row->diagnosis);

        $row->fill([
            ...$this->scoreAttributes($input['score'] ?? null),
            // A calculated diagnosis keeps the experience of its people (18 · 18.4).
            ...(array_key_exists('experience', $input) ? $this->experienceAttributes($input['experience']) : []),
            'experience_met_example' => $input['experience_met_example'] ?? null,
            'experience_unmet_example' => $input['experience_unmet_example'] ?? null,
            'reasoning' => $input['reasoning'] ?? null,
            'assigned_on' => $input['assigned_on'] ?? $row->assigned_on,
        ]);

        // A loaded row changed by hand is marked (04 · 4.5).
        if ($row->isAggregated() && $row->isDirty()) {
            $row->manually_modified = true;
        }

        $row->save();
        $this->refreshManualPoint($row->diagnosis);
    }

    /**
     * A calculated row written by hand (13 · 13.5, 18): the reason is required,
     * who and when is kept, and the automatic value stays next to it.
     */
    public function overrideRow(DiagnosisRow $row, ?string $score, string $reason, User $admin): void
    {
        $this->ensureCorrectable($row->diagnosis);

        $row->forceFill([
            ...$this->scoreAttributes($score),
            'override_reason' => trim($reason),
            'overridden_by' => $admin->id,
            'overridden_at' => now(),
        ])->save();

        $this->logOverride($row->diagnosis, $row->label(), 'override', $admin, $row->automatic_score, $row->automatic_score_state, $row->score, $row->score_state, trim($reason));
        $this->refreshManualPoint($row->diagnosis);
        $this->recalculate($row->diagnosis);
    }

    /**
     * Back to the calculated value of the row.
     */
    public function resetRowOverride(DiagnosisRow $row): void
    {
        $this->ensureCorrectable($row->diagnosis);

        $row->forceFill([
            'score' => $row->automatic_score,
            'score_state' => $row->automatic_score_state,
            'override_reason' => null,
            'overridden_by' => null,
            'overridden_at' => null,
        ])->save();

        $this->logOverride($row->diagnosis, $row->label(), 'reset', auth('admin')->user(), $row->automatic_score, $row->automatic_score_state);
        $this->refreshManualPoint($row->diagnosis);
        $this->recalculate($row->diagnosis);
    }

    /**
     * The point of a process diagnosis written by hand (13 · 13.5).
     */
    public function overrideScore(Diagnosis $diagnosis, ?string $score, string $reason, User $admin): void
    {
        $this->ensureCorrectable($diagnosis);

        $diagnosis->forceFill([
            ...$this->scoreAttributes($score),
            'score_override_reason' => trim($reason),
            'score_overridden_by' => $admin->id,
            'score_overridden_at' => now(),
        ])->save();

        $this->logOverride($diagnosis, __('admin.diagnoses.process_point'), 'override', $admin, $diagnosis->automatic_score, $diagnosis->automatic_score_state, $diagnosis->score, $diagnosis->score_state, trim($reason));
        $this->recalculate($diagnosis);
    }

    public function resetScoreOverride(Diagnosis $diagnosis): void
    {
        $this->ensureCorrectable($diagnosis);

        $diagnosis->forceFill([
            'score' => $diagnosis->automatic_score,
            'score_state' => $diagnosis->automatic_score_state,
            'score_override_reason' => null,
            'score_overridden_by' => null,
            'score_overridden_at' => null,
        ])->save();

        $this->logOverride($diagnosis, __('admin.diagnoses.process_point'), 'reset', auth('admin')->user(), $diagnosis->automatic_score, $diagnosis->automatic_score_state);
        $this->recalculate($diagnosis);
    }

    /**
     * The point of a Folyamat diagnosis recorded by hand: the mean of its value
     * rows, one decimal, unless written over (13 · 13.1, 13.3). A calculated
     * one gets it from the calculation.
     */
    public function refreshManualPoint(Diagnosis $diagnosis): void
    {
        if ($diagnosis->level !== DiagnosisLevel::Process || $diagnosis->isCalculated()) {
            return;
        }

        $rows = $diagnosis->valueRows()->get();
        $scores = $rows->where('score_state', ScoreState::Scored)->pluck('score')->filter(fn ($score) => $score !== null)->map(fn ($score) => (float) $score)->values()->all();
        $point = ScoreMath::round1(ScoreMath::mean($scores));
        $state = match (true) {
            $point !== null => ScoreState::Scored,
            $rows->contains('score_state', ScoreState::NoTrace) => ScoreState::NoTrace,
            default => null,
        };

        $diagnosis->forceFill([
            'automatic_score' => $point,
            'automatic_score_state' => $state,
            ...($diagnosis->score_overridden_at === null ? ['score' => $point, 'score_state' => $state] : []),
        ])->save();
    }

    /**
     * Who wrote what over and when, kept with a Folyamat diagnosis even after
     * going back to the automatic value (13 · 13.5).
     */
    private function logOverride(Diagnosis $diagnosis, string $target, string $action, ?User $admin, ?float $automatic, ?ScoreState $automaticState, ?float $value = null, ?ScoreState $valueState = null, ?string $reason = null): void
    {
        if ($diagnosis->level !== DiagnosisLevel::Process) {
            return;
        }

        $label = fn (?float $score, ?ScoreState $state) => $state === ScoreState::Scored && $score !== null ? number_format($score, 1, ',', '') : '–';
        $aggregation = $diagnosis->aggregation ?? [];
        $aggregation['overrides'] = [...($aggregation['overrides'] ?? []), [
            'target' => $target,
            'action' => $action,
            'by' => $admin?->name,
            'at' => now()->toIso8601String(),
            'automatic' => $label($automatic, $automaticState),
            'value' => $action === 'override' ? $label($value, $valueState) : null,
            'reason' => $reason,
        ]];

        $diagnosis->forceFill(['aggregation' => $aggregation])->save();
    }

    public function removeRow(DiagnosisRow $row): void
    {
        $this->ensureEditable($row->diagnosis);

        if ($row->kind !== DiagnosisRowKind::Value) {
            throw new DiagnosisException(__('admin.diagnoses.tree_row_fixed'));
        }

        $row->delete();
        $this->refreshManualPoint($row->diagnosis);
    }

    /**
     * A calculated diagnosis can be corrected after publishing too, with a
     * reason and a new snapshot (21 · 21.4); everything else follows 04.
     */
    public function ensureCorrectable(Diagnosis $diagnosis): void
    {
        $main = $diagnosis->main ?? $diagnosis->parent?->main;

        if ($main !== null && $main->stage === MainDiagnosisStage::Published) {
            return;
        }

        $this->ensureEditable($diagnosis);
    }

    public function ensureEditable(Diagnosis $diagnosis): void
    {
        if (! $diagnosis->status->isEditable()) {
            throw new DiagnosisException(__('admin.diagnoses.published_readonly'));
        }
    }

    /**
     * The score is required on every value row, except for Megélés (04 · 4.4).
     */
    private function ensureScored(Diagnosis $diagnosis): void
    {
        if ($diagnosis->type->isExperience()) {
            return;
        }

        $missing = $diagnosis->valueRows()->whereNull('score_state')->with('value')->get();

        if ($missing->isNotEmpty()) {
            throw new DiagnosisException(__('admin.diagnoses.scores_missing', [
                'values' => $missing->map(fn (DiagnosisRow $row) => $row->label())->implode(', '),
            ]));
        }
    }

    /**
     * @return array{score: ?float, score_state: ?ScoreState}
     */
    private function scoreAttributes(?string $score): array
    {
        return match (true) {
            $score === null || $score === '' => ['score' => null, 'score_state' => null],
            $score === self::NO_TRACE => ['score' => null, 'score_state' => ScoreState::NoTrace],
            default => ['score' => (float) $score, 'score_state' => ScoreState::Scored],
        };
    }

    /**
     * @return array{experience: ?float, experience_state: ?ExperienceState}
     */
    private function experienceAttributes(?string $experience): array
    {
        return match (true) {
            $experience === null || $experience === '' => ['experience' => null, 'experience_state' => null],
            $experience === self::DONT_KNOW => ['experience' => null, 'experience_state' => ExperienceState::DontKnow],
            default => ['experience' => (float) $experience, 'experience_state' => ExperienceState::Answered],
        };
    }

    /**
     * A hand-written value changes the ring, the step and the process rows (18 · 18.4).
     */
    private function recalculate(Diagnosis $diagnosis): void
    {
        $main = $diagnosis->main ?? $diagnosis->parent?->main;

        if ($main !== null) {
            RecalculateMainDiagnosis::dispatch($main);
        }
    }

    private function logTransition(Diagnosis $diagnosis, ?DiagnosisStatus $from, DiagnosisStatus $to): void
    {
        $diagnosis->transitions()->create([
            'from_status' => $from,
            'to_status' => $to,
            'created_by' => auth()->id(),
        ]);
    }
}
