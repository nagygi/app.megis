<?php

namespace App\Services\Capabilities;

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\CapabilityState;
use App\Enums\ConsentKind;
use App\Enums\FindingStatus;
use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Models\CapabilityAssessment;
use App\Models\CapabilityFinding;
use App\Models\CapabilityTrace;
use App\Models\Consent;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * What a published diagnosis says about capabilities to its readers (V12,
 * M3, NM-12): only rows an admin approved, only "jelen lehet" and
 * "hiányzik", one per capability. State and confidence, never a code, a
 * step or a maturity: that comes with the second diagnosis (ER-04).
 */
class CapabilityOverview
{
    /**
     * @return array{rows: list<array<string, mixed>>, strengths: list<array<string, mixed>>, gaps: list<array<string, mixed>>}
     */
    public function for(MainDiagnosis $main, User $viewer): array
    {
        if (! in_array($main->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true)) {
            return ['rows' => [], 'strengths' => [], 'gaps' => []];
        }

        $assessments = CapabilityAssessment::query()
            ->where('diagnosis_id', $main->diagnosis_id)
            ->whereNotNull('approved_at')
            ->whereIn('state', [CapabilityState::Present, CapabilityState::Absent])
            ->whereHas('capability')
            ->with(['capability', 'blocker'])
            ->get();

        $levels = $this->levelOrder($main->subject);
        $quotes = $this->quotes($main, $viewer, $assessments->pluck('capability_id')->unique()->all());

        $rows = $assessments
            ->groupBy('capability_id')
            ->map(function (Collection $group) use ($levels, $quotes): array {
                /** @var CapabilityAssessment $shown */
                $shown = $group->sortBy([
                    fn (CapabilityAssessment $a, CapabilityAssessment $b) => array_search($a->subject_level, $levels, true) <=> array_search($b->subject_level, $levels, true),
                    fn (CapabilityAssessment $a, CapabilityAssessment $b) => self::rank($b->confidence) <=> self::rank($a->confidence),
                ])->first();
                $counts = $shown->automatic_basis['counts'] ?? [];

                return [
                    'id' => $shown->capability_id,
                    'name' => $shown->capability->name,
                    'definition' => $shown->capability->definition,
                    'statement' => $shown->capability->statement,
                    'state' => $shown->state,
                    'confidence' => $shown->confidence,
                    'little_data' => $shown->confidence === CapabilityConfidence::Low,
                    'traces' => (int) ($counts['positive'] ?? 0) + (int) ($counts['negative'] ?? 0),
                    'uneven' => $group->contains(fn (CapabilityAssessment $assessment) => $assessment->uneven),
                    'blocker' => $shown->blocker?->name,
                    'tempting' => (bool) $shown->capability->tempting,
                    'quote' => $quotes[$shown->capability_id] ?? null,
                ];
            })
            ->sortBy([
                fn (array $a, array $b) => ($a['state'] === CapabilityState::Present ? 0 : 1) <=> ($b['state'] === CapabilityState::Present ? 0 : 1),
                fn (array $a, array $b) => self::rank($b['confidence']) <=> self::rank($a['confidence']),
                fn (array $a, array $b) => $b['traces'] <=> $a['traces'],
                fn (array $a, array $b) => $a['name'] <=> $b['name'],
            ])
            ->values();

        $limit = (int) config('diagnosis.capability_card.'.$main->product_key, 3);

        return [
            'rows' => $rows->all(),
            'strengths' => $rows->where('state', CapabilityState::Present)->take($limit)->values()->all(),
            // A tempting capability on little data is not put forward as missing (NM-15).
            'gaps' => $rows->where('state', CapabilityState::Absent)
                ->reject(fn (array $row) => $row['tempting'] && $row['little_data'])
                ->take($limit)
                ->values()
                ->all(),
        ];
    }

    /**
     * The levels in the order the diagnosis speaks of: a team first, a person or a private person themself.
     *
     * @return list<CapabilityLevel>
     */
    private function levelOrder(MainDiagnosisSubject $subject): array
    {
        return match ($subject) {
            MainDiagnosisSubject::Team => [CapabilityLevel::Team, CapabilityLevel::Organization, CapabilityLevel::Individual, CapabilityLevel::Ecosystem],
            MainDiagnosisSubject::Person, MainDiagnosisSubject::Individual => [CapabilityLevel::Individual, CapabilityLevel::Team, CapabilityLevel::Organization, CapabilityLevel::Ecosystem],
        };
    }

    /**
     * One quote per capability from a confirmed finding: the viewer's own, or
     * a peer's when the diagnosis shows peer quotes and the peer agreed to it
     * (23 · 23.4, 25 · 25.2). Without a name; raw traces only (NY-03).
     *
     * @param  list<int>  $capabilityIds
     * @return array<int, array{text: string, who: string, date: ?string}>
     */
    private function quotes(MainDiagnosis $main, User $viewer, array $capabilityIds): array
    {
        if ($capabilityIds === []) {
            return [];
        }

        $materials = Material::query()->where('parent_diagnosis_id', $main->diagnosis_id)->whereNotNull('user_id')->get(['id', 'user_id'])->keyBy('id');
        $quotingPeers = $main->show_peer_quotes
            ? Consent::query()->where('main_diagnosis_id', $main->id)->where('kind', ConsentKind::Quotes)->whereNull('revoked_at')->pluck('user_id')->all()
            : [];
        $visible = fn (?int $userId) => $userId === $viewer->id || ($userId !== $main->leader_id && in_array($userId, $quotingPeers, true));

        $findings = CapabilityFinding::query()
            ->whereIn('capability_id', $capabilityIds)
            ->whereIn('material_id', $materials->keys())
            ->where('status', FindingStatus::Confirmed)
            ->with(['traces' => fn ($query) => $query->usable()->where('is_raw', true)->where('speaker_role', '!=', 'interviewer')])
            ->get();

        $quotes = [];

        foreach ($findings->groupBy('capability_id') as $capabilityId => $group) {
            $trace = $group->flatMap(fn (CapabilityFinding $finding) => $finding->traces)
                ->reject(fn (CapabilityTrace $trace) => $trace->isBlocked())
                ->filter(fn (CapabilityTrace $trace) => $visible($materials->get($trace->material_id)?->user_id))
                ->sortByDesc(fn (CapabilityTrace $trace) => $trace->recorded_on?->timestamp ?? 0)
                ->first();

            if ($trace !== null) {
                $quotes[$capabilityId] = [
                    'text' => $trace->quote,
                    'who' => __($materials->get($trace->material_id)?->user_id === $viewer->id ? 'capabilities.library.quote_you' : 'capabilities.library.quote_peer'),
                    'date' => $trace->recorded_on?->format('m.d.'),
                ];
            }
        }

        return $quotes;
    }

    private static function rank(?CapabilityConfidence $confidence): int
    {
        return match ($confidence) {
            CapabilityConfidence::High => 3,
            CapabilityConfidence::Medium => 2,
            CapabilityConfidence::Disputed => 1,
            default => 0,
        };
    }
}
