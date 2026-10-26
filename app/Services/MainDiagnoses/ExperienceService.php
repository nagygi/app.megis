<?php

namespace App\Services\MainDiagnoses;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\ExperienceState;
use App\Enums\TreeElement;
use App\Jobs\RecalculateMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\MainDiagnosis;
use App\Models\User;
use App\Models\Version;
use App\Services\Diagnoses\DiagnosisService;
use Illuminate\Support\Facades\DB;

/**
 * "Nézzük egyben" (V5, 08 · 8.3) and the peer's T4 (25 · 25.4): the values
 * and the four elements of the brand base, each 1–6 or "nem tudom". Sent,
 * it becomes the user's Megélés subdiagnosis with the value set version it
 * answered; sent again before reading, the same diagnosis is updated.
 */
class ExperienceService
{
    public const DONT_KNOW = 'dont_know';

    public function __construct(
        private DiagnosisService $diagnoses,
        private StepProgress $progress,
    ) {}

    /**
     * The rows of the page from a value set version: key => label, meaning, ends.
     *
     * @return list<array{key: string, kind: string, label: string, meaning: ?string, low: ?string, high: ?string, value_id: ?int, value_version_id: ?int, element: ?string}>
     */
    public function rows(array $snapshot): array
    {
        $rows = [];

        foreach ($snapshot['values'] ?? [] as $value) {
            $rows[] = [
                'key' => 'value:'.$value['key'],
                'kind' => DiagnosisRowKind::Value->value,
                'label' => $value['name'],
                'meaning' => $value['meaning'] ?? null,
                'low' => $value['low_example'] ?? null,
                'high' => $value['high_example'] ?? null,
                'value_id' => $value['value_id'],
                'value_version_id' => $value['source_version_id'] ?? null,
                'element' => null,
            ];
        }

        foreach (TreeElement::cases() as $element) {
            $field = $element->valueSetField();

            $rows[] = [
                'key' => 'tree:'.$element->value,
                'kind' => DiagnosisRowKind::TreeElement->value,
                'label' => $element->label(),
                'meaning' => $field === null
                    ? implode(' · ', array_column($snapshot['values'] ?? [], 'name'))
                    : ($snapshot[$field] ?? null),
                'low' => null,
                'high' => null,
                'value_id' => null,
                'value_version_id' => null,
                'element' => $element->value,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $answers  row key => "1"…"6" or "dont_know"
     */
    public function submit(MainDiagnosis $main, User $user, Version $version, array $answers): Diagnosis
    {
        $rows = $this->rows($version->snapshot);

        $experience = DB::transaction(function () use ($main, $user, $version, $answers, $rows): Diagnosis {
            $diagnosis = $this->progress->experienceDiagnosis($main, $user);
            $parent = $main->diagnosis;

            if ($diagnosis === null) {
                $diagnosis = $this->diagnoses->create([
                    'name' => mb_substr(__('diagnosis.experience.diagnosis_name', ['name' => $user->name, 'subject' => $main->subjectName()]), 0, 255),
                    'organization_id' => $parent->organization_id,
                    'level' => DiagnosisLevel::User,
                    'user_id' => $user->id,
                    'diagnosis_type_id' => DiagnosisType::byKey(DiagnosisType::EXPERIENCE)->id,
                    'diagnosed_on' => today()->toDateString(),
                    'parent_id' => $parent->id,
                    'value_set_version_id' => $version->id,
                ], DiagnosisCreator::Automatic, $parent->status);
            } else {
                $diagnosis->update(['value_set_version_id' => $version->id, 'diagnosed_on' => today()->toDateString()]);
            }

            $diagnosis->valueRows()->delete();
            $position = 0;

            foreach ($rows as $row) {
                $attributes = $this->experienceAttributes($answers[$row['key']] ?? null);

                if ($row['kind'] === DiagnosisRowKind::Value->value) {
                    $diagnosis->rows()->create([
                        'kind' => DiagnosisRowKind::Value,
                        'value_id' => $row['value_id'],
                        'value_version_id' => $row['value_version_id'],
                        'position' => $position++,
                        'assigned_on' => $diagnosis->diagnosed_on,
                        ...$attributes,
                    ]);

                    continue;
                }

                $diagnosis->rows()->where('tree_element', $row['element'])->update($attributes);
            }

            return $diagnosis;
        });

        // The team experience and the gaps change (18 · 18.4).
        RecalculateMainDiagnosis::dispatch($main);

        return $experience;
    }

    /**
     * The answers of a sent Megélés by row key, as the page keeps them.
     *
     * @return array<string, string>
     */
    public function answersOf(Diagnosis $diagnosis): array
    {
        $answers = [];
        $snapshot = $diagnosis->valueSetVersion?->snapshot ?? [];
        $keysByValue = collect($snapshot['values'] ?? [])->pluck('key', 'value_id');

        foreach ($diagnosis->rows()->get() as $row) {
            $key = $row->tree_element !== null ? 'tree:'.$row->tree_element->value : 'value:'.$keysByValue->get($row->value_id);
            $answers[$key] = match ($row->experience_state?->value) {
                'dont_know' => self::DONT_KNOW,
                'answered' => (string) (int) $row->experience,
                default => null,
            };
        }

        return array_filter($answers);
    }

    /**
     * The user's answers: the unsent draft, else the sent Megélés (08 · 8.3).
     *
     * @return array<string, string>
     */
    public function answersFor(MainDiagnosis $main, User $user, ?array $draft): array
    {
        if ($draft !== null) {
            return $draft;
        }

        $sent = $this->progress->experienceDiagnosis($main, $user);

        return $sent === null ? [] : $this->answersOf($sent);
    }

    /**
     * @return array{experience: ?float, experience_state: ExperienceState}
     */
    private function experienceAttributes(?string $answer): array
    {
        return match (true) {
            $answer === self::DONT_KNOW => ['experience' => null, 'experience_state' => ExperienceState::DontKnow],
            $answer !== null && in_array($answer, ['1', '2', '3', '4', '5', '6'], true) => ['experience' => (float) $answer, 'experience_state' => ExperienceState::Answered],
            default => ['experience' => null, 'experience_state' => ExperienceState::Missing],
        };
    }
}
