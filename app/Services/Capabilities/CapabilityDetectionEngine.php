<?php

namespace App\Services\Capabilities;

use App\Ai\AiContext;
use App\Ai\Tasks\DetectCapabilityTracesTask;
use App\Ai\Tasks\MatchCapabilityPatternsTask;
use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\FindingKind;
use App\Enums\FindingStatus;
use App\Enums\TraceCandidate;
use App\Enums\TraceDirection;
use App\Enums\TraceDirectness;
use App\Models\Capability;
use App\Models\CapabilityFinding;
use App\Models\CapabilityTrace;
use App\Models\Material;
use App\Services\Ai\AiGateway;
use App\Services\Engine\TranscriptChunker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The capability measurement of a material in two steps (NM-12): A traces
 * per part of the transcript against the nameless capability catalog, B the
 * traces of each capability matched to its patterns, with the detector-error
 * patterns as prohibitions. Every finding stays proposed until an admin
 * decides; running it again replaces only what no one has touched.
 */
class CapabilityDetectionEngine
{
    private const LEVELS = [
        'egyen' => CapabilityLevel::Individual,
        'csapat' => CapabilityLevel::Team,
        'szervezet' => CapabilityLevel::Organization,
        'hatarfelulet' => CapabilityLevel::Organization,
        'okoszisztema' => CapabilityLevel::Ecosystem,
    ];

    public function __construct(
        private AiGateway $ai,
        private TranscriptChunker $chunker,
        private QuoteVerifier $quotes,
    ) {}

    /**
     * @return bool false when there is nothing to measure against (an empty Képességtár)
     */
    public function measure(Material $material, AiContext $context): bool
    {
        $catalog = CapabilityCatalog::build();

        if ($catalog->isEmpty()) {
            Log::channel('ai')->info('Capability measurement skipped: the Képességtár is empty.', ['material_id' => $material->id]);

            return false;
        }

        $this->detect($material, $catalog, $context);
        $this->match($material, $context);

        return true;
    }

    /**
     * Step A. A trace an admin excluded, added or reviewed through a finding stays.
     */
    public function detect(Material $material, CapabilityCatalog $catalog, AiContext $context): void
    {
        DB::transaction(function () use ($material): void {
            $material->capabilityFindings()->whereNull('reviewed_at')->delete();
            $material->capabilityTraces()
                ->whereNull('created_by')
                ->whereNull('excluded_at')
                ->whereDoesntHave('findings')
                ->delete();
        });

        $transcript = $material->transcriptForEngine();
        $numbered = collect(preg_split('/\R/u', $transcript) ?: [])
            ->map(fn (string $line, int $index) => ($index + 1).'. '.$line)
            ->implode("\n");
        $existing = $material->capabilityTraces()->get();
        $dropped = 0;

        foreach ($this->chunker->chunks($numbered, (int) config('megis.engine.chunk_characters')) as $chunk) {
            $result = $this->ai->run(new DetectCapabilityTracesTask($catalog->forPrompt(), $this->header($material), $chunk), $context);

            foreach ($result->structured['traces'] ?? [] as $row) {
                $quote = trim((string) ($row['quote'] ?? ''));

                // The interviewer is never a trace; a quote that is not in the material is dropped (6.1).
                if ($quote === '' || ($row['speaker_role'] ?? null) === 'interviewer' || ! $this->quotes->occursIn($quote, $transcript)) {
                    $dropped++;

                    continue;
                }

                $level = (string) ($row['level'] ?? '');
                $attributes = [
                    'organization_id' => $material->organization_id,
                    'ai_call_id' => $result->call->id,
                    'speaker' => filled($row['speaker'] ?? null) ? mb_substr(trim($row['speaker']), 0, 255) : null,
                    'speaker_role' => in_array($row['speaker_role'] ?? null, DetectCapabilityTracesTask::SPEAKER_ROLES, true) ? $row['speaker_role'] : 'unknown',
                    'quote' => mb_substr($quote, 0, 2000),
                    'location' => filled($row['location'] ?? null) ? mb_substr(trim($row['location']), 0, 255) : null,
                    'subject_level' => self::LEVELS[$level] ?? CapabilityLevel::Organization,
                    'boundary' => $level === 'hatarfelulet',
                    'direction' => TraceDirection::tryFrom((string) ($row['direction'] ?? '')) ?? TraceDirection::Mixed,
                    'directness' => TraceDirectness::tryFrom((string) ($row['directness'] ?? '')) ?? TraceDirectness::SelfReport,
                    'is_raw' => (bool) ($row['raw'] ?? true),
                    'flags' => array_values(array_intersect(CapabilityTrace::FLAGS, (array) ($row['flags'] ?? []))),
                    'preconditions' => $this->preconditions((array) ($row['preconditions'] ?? [])),
                    'suggested_capability_ids' => collect((array) ($row['capabilities'] ?? []))
                        ->map(fn ($ref) => $catalog->resolve((string) $ref)?->id)
                        ->filter()->unique()->take(3)->values()->all(),
                    'alternative_reading' => filled($row['alternative_reading'] ?? null) ? mb_substr(trim($row['alternative_reading']), 0, 2000) : null,
                    'candidate' => TraceCandidate::tryFrom((string) ($row['candidate'] ?? '')),
                    'candidate_note' => filled($row['candidate_note'] ?? null) ? mb_substr(trim($row['candidate_note']), 0, 2000) : null,
                    'recorded_on' => $this->recordedOn($material),
                ];

                // The same quote again (a kept trace, or a second part of the transcript): one trace.
                $same = $existing->first(fn (CapabilityTrace $trace) => mb_strtolower($trace->quote) === mb_strtolower($attributes['quote']));

                if ($same !== null) {
                    if ($same->created_by === null && $same->excluded_at === null) {
                        $same->update(['suggested_capability_ids' => array_values(array_unique([...$same->suggested_capability_ids, ...$attributes['suggested_capability_ids']]))]);
                    }

                    continue;
                }

                $existing->push($material->capabilityTraces()->create($attributes));
            }
        }

        if ($dropped > 0) {
            Log::channel('ai')->info('Capability traces dropped: interviewer or quote not in the material.', ['material_id' => $material->id, 'dropped' => $dropped]);
        }
    }

    /**
     * Step B, per capability the traces point at, the most mentioned first.
     */
    public function match(Material $material, AiContext $context): void
    {
        $traces = $material->capabilityTraces()->usable()->where('speaker_role', '!=', 'interviewer')->orderBy('id')->get();
        $byCapability = $this->byCapability($traces);
        $limit = (int) config('megis.capabilities.measurement.max_capabilities_per_material');

        foreach ($byCapability->take($limit) as $capabilityId => $capabilityTraces) {
            $capability = Capability::query()->find($capabilityId);

            if ($capability === null) {
                continue;
            }

            $this->matchCapability($material, $capability, $capabilityTraces->take((int) config('megis.capabilities.measurement.max_traces_per_capability'))->values(), $context);
        }
    }

    /**
     * @param  Collection<int, CapabilityTrace>  $traces
     */
    private function matchCapability(Material $material, Capability $capability, Collection $traces, AiContext $context): void
    {
        $catalog = PatternCatalog::for($capability);
        $byRef = $traces->mapWithKeys(fn (CapabilityTrace $trace, int $index) => ['N'.($index + 1) => $trace]);

        $result = $this->ai->run(new MatchCapabilityPatternsTask(
            $catalog->capabilityCard(),
            $catalog->patternsForPrompt(),
            $catalog->detectorsForPrompt(),
            $byRef->map(fn (CapabilityTrace $trace, string $ref) => [
                'id' => $ref,
                'quote' => $trace->quote,
                'speaker_role' => $trace->speaker_role,
                'level' => $trace->boundary ? 'hatarfelulet' : array_search($trace->subject_level, self::LEVELS, true),
                'direction' => $trace->direction->value,
                'directness' => $trace->directness->value,
                'raw' => $trace->is_raw,
                'flags' => $trace->flags,
                'alternative_reading' => $trace->alternative_reading,
            ])->values()->all(),
        ), $context);

        foreach ($result->structured['blocked'] ?? [] as $row) {
            $trace = $byRef->get(strtoupper(trim((string) ($row['trace'] ?? ''))));
            $detector = $catalog->detector((string) ($row['detector'] ?? ''));

            if ($trace !== null && $detector !== null && ! collect($trace->detector_blocks)->contains('pattern_id', $detector->id)) {
                $trace->update(['detector_blocks' => [...$trace->detector_blocks, ['pattern_id' => $detector->id, 'reason' => mb_substr(trim((string) ($row['reason'] ?? '')), 0, 1000)]]]);
            }
        }

        foreach ($result->structured['findings'] ?? [] as $row) {
            $ref = trim((string) ($row['pattern'] ?? ''));
            $pattern = $catalog->pattern($ref);
            $interpretation = ! $catalog->hasPatterns() || strtolower($ref) === 'definition';
            $findingTraces = collect((array) ($row['traces'] ?? []))->map(fn ($trace) => $byRef->get(strtoupper(trim((string) $trace))))->filter()->unique('id');

            if ((! $interpretation && $pattern === null) || $findingTraces->isEmpty()) {
                Log::channel('ai')->warning('Capability matching: the answer names an unknown pattern or no trace.', [
                    'capability_id' => $capability->id,
                    'pattern' => $ref,
                    'ai_call_id' => $result->call->id,
                ]);

                continue;
            }

            $level = self::LEVELS[(string) ($row['level'] ?? '')] ?? $findingTraces->first()->subject_level;
            $key = ['material_id' => $material->id, 'capability_id' => $capability->id, 'pattern_id' => $interpretation ? null : $pattern->id, 'subject_level' => $level];

            // What an admin already decided stays as it is.
            if (CapabilityFinding::query()->where($key)->whereNotNull('reviewed_at')->exists()) {
                continue;
            }

            $finding = CapabilityFinding::query()->create([
                ...$key,
                'organization_id' => $material->organization_id,
                'pattern_version_id' => $interpretation ? null : $pattern->latestVersion?->id,
                'kind' => $interpretation ? FindingKind::Interpretation : FindingKind::Pattern,
                'status' => FindingStatus::Proposed,
                // The machine gives at most medium (KO-01); an interpretation at most low.
                'machine_confidence' => ! $interpretation && ($row['confidence'] ?? null) === 'medium' ? CapabilityConfidence::Medium : CapabilityConfidence::Low,
                'reasoning' => filled($row['reasoning'] ?? null) ? mb_substr(trim($row['reasoning']), 0, 2000) : null,
                // NY-26: a person approves the first finding of a hypothesis pattern, and every interpretation.
                'needs_first_approval' => $interpretation || $pattern->maturity()->isHypothesis(),
                'ai_call_id' => $result->call->id,
            ]);

            $finding->traces()->sync($findingTraces->pluck('id')->all());
        }
    }

    /**
     * The traces under each capability they point at, the most mentioned capability first.
     *
     * @param  Collection<int, CapabilityTrace>  $traces
     * @return Collection<int, Collection<int, CapabilityTrace>>
     */
    private function byCapability(Collection $traces): Collection
    {
        $grouped = [];

        foreach ($traces as $trace) {
            foreach ($trace->suggested_capability_ids as $capabilityId) {
                $grouped[$capabilityId][] = $trace;
            }
        }

        return collect($grouped)
            ->map(fn (array $group) => collect($group))
            ->sortByDesc(fn (Collection $group) => $group->count());
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array{code: string, direction: string}>
     */
    private function preconditions(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row) && preg_match('/^E[1-9]$/', strtoupper(trim((string) ($row['code'] ?? '')))) === 1)
            ->map(fn (array $row) => [
                'code' => strtoupper(trim((string) $row['code'])),
                'direction' => (TraceDirection::tryFrom((string) ($row['direction'] ?? '')) ?? TraceDirection::Mixed)->value,
            ])
            ->unique('code')
            ->values()
            ->all();
    }

    private function header(Material $material): string
    {
        return implode("\n", [
            'Anyag: '.$material->context->label().($material->is_situation ? ' (helyzet-jegyzet)' : ''),
            'Forrás: '.($material->source->hasAudio() ? 'gépi átirat; a beszélő-címkék gépiek, tartalom alapján ellenőrizd' : 'leírt szöveg'),
            'Dátum: '.($this->recordedOn($material) ?? 'nem ismert'),
        ]);
    }

    private function recordedOn(Material $material): ?string
    {
        $material->loadMissing('occasion');

        return ($material->diagnosed_on ?? $material->occasion?->occurred_at ?? $material->created_at)?->toDateString();
    }
}
