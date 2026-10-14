<?php

namespace App\Services\Diagnoses;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisRowKind;
use App\Enums\DiagnosisStatus;
use App\Enums\ExperienceState;
use App\Enums\ScoreState;
use App\Enums\TreeElement;
use App\Enums\ValueOrigin;
use App\Exceptions\DiagnosisException;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
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

            foreach (TreeElement::cases() as $position => $element) {
                $diagnosis->rows()->create([
                    'kind' => DiagnosisRowKind::TreeElement,
                    'tree_element' => $element,
                    'position' => $position,
                    'assigned_on' => $diagnosis->diagnosed_on,
                ]);
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

        $diagnosis->update($attributes);
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
     * Binds the value with its current Értéktár version (04 · 4.4, 03 · 3.3).
     */
    public function addValue(Diagnosis $diagnosis, Value $value): DiagnosisRow
    {
        $this->ensureEditable($diagnosis);

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
            ...$this->experienceAttributes($input['experience'] ?? null),
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
    }

    public function removeRow(DiagnosisRow $row): void
    {
        $this->ensureEditable($row->diagnosis);

        if ($row->kind !== DiagnosisRowKind::Value) {
            throw new DiagnosisException(__('admin.diagnoses.tree_row_fixed'));
        }

        $row->delete();
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

    private function logTransition(Diagnosis $diagnosis, ?DiagnosisStatus $from, DiagnosisStatus $to): void
    {
        $diagnosis->transitions()->create([
            'from_status' => $from,
            'to_status' => $to,
            'created_by' => auth()->id(),
        ]);
    }
}
