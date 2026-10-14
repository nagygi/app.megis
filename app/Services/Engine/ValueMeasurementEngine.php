<?php

namespace App\Services\Engine;

use App\Ai\AiContext;
use App\Ai\Tasks\DetectValuesTask;
use App\Ai\Tasks\MatchValuesTask;
use App\Ai\Tasks\ScoreValuesTask;
use App\Enums\ScoreResultState;
use App\Models\Material;
use App\Models\RawDetection;
use App\Services\Ai\AiGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The value measurement in three steps (06 · 6.4): A free detection,
 * B matching to the Értéktár by meaning, C scoring per matched value.
 * Every result keeps the AI call it came from, so the model and the prompt
 * version are known (NFR · 11). Running it again replaces the earlier result.
 */
class ValueMeasurementEngine
{
    public function __construct(
        private AiGateway $ai,
        private TranscriptChunker $chunker,
    ) {}

    /**
     * @param  list<string|null>  $speakers  null scores every speaker together (a mirror)
     */
    public function analyze(Material $material, ValueCatalog $catalog, AiContext $context, array $speakers = [null]): void
    {
        $this->detect($material, $context);
        $this->match($material, $catalog, $context);

        foreach ($speakers as $speaker) {
            $this->score($material, $speaker, $catalog, $context);
        }
    }

    /**
     * Step A: read the transcript in parts, keep every detection raw.
     */
    public function detect(Material $material, AiContext $context): void
    {
        DB::transaction(function () use ($material): void {
            $material->scores()->delete();
            $material->detections()->delete();
        });

        foreach ($this->chunker->chunks($material->transcriptForEngine(), (int) config('megis.engine.chunk_characters')) as $chunk) {
            $result = $this->ai->run(new DetectValuesTask($chunk), $context);

            foreach ($result->structured['detections'] ?? [] as $detection) {
                $quote = trim((string) ($detection['quote'] ?? ''));
                $freeName = trim((string) ($detection['free_name'] ?? ''));

                if ($quote === '' || $freeName === '') {
                    continue;
                }

                $material->detections()->create([
                    'ai_call_id' => $result->call->id,
                    'speaker' => filled($detection['speaker'] ?? null) ? mb_substr(trim($detection['speaker']), 0, 255) : null,
                    'quote' => mb_substr($quote, 0, 2000),
                    'free_name' => mb_substr(mb_strtoupper(mb_substr($freeName, 0, 1)).mb_substr($freeName, 1), 0, 255),
                    'reasoning' => isset($detection['reasoning']) ? mb_substr(trim($detection['reasoning']), 0, 2000) : null,
                    'strength' => isset($detection['strength']) ? max(1, min(6, (int) $detection['strength'])) : null,
                ]);
            }
        }
    }

    /**
     * Step B: match the detections in batches to the nameless catalog.
     */
    public function match(Material $material, ValueCatalog $catalog, AiContext $context): void
    {
        if ($catalog->isEmpty()) {
            return;
        }

        foreach ($material->detections()->orderBy('id')->get()->chunk((int) config('megis.engine.matching_batch')) as $batch) {
            $byCode = $batch->values()->mapWithKeys(fn (RawDetection $detection, int $index) => ['D'.($index + 1) => $detection]);

            $result = $this->ai->run(new MatchValuesTask(
                $catalog->forPrompt(),
                $byCode->map(fn (RawDetection $detection, string $code) => [
                    'id' => $code,
                    'free_name' => $detection->free_name,
                    'reasoning' => $detection->reasoning,
                    'quote' => $detection->quote,
                ])->values()->all(),
            ), $context);

            foreach ($result->structured['results'] ?? [] as $row) {
                $detection = $byCode->get(strtoupper(trim((string) ($row['detection'] ?? ''))));

                if ($detection === null) {
                    continue;
                }

                foreach ($row['matches'] ?? [] as $match) {
                    $value = $catalog->resolve((string) ($match['ref'] ?? ''));
                    $confidence = max(0.0, min(1.0, (float) ($match['confidence'] ?? 0)));

                    if ($value === null) {
                        continue;
                    }

                    $detection->matches()->updateOrCreate(['value_id' => $value->id], [
                        'value_version_id' => $value->latestVersion?->id,
                        'confidence' => $confidence,
                        'ai_call_id' => $result->call->id,
                    ]);
                }
            }
        }
    }

    /**
     * Step C: score the values matched in one speaker's detections.
     */
    public function score(Material $material, ?string $speaker, ValueCatalog $catalog, AiContext $context): void
    {
        $detections = $this->matchedDetections($material, $speaker);

        if ($detections->isEmpty()) {
            return;
        }

        $byCode = $detections->values()->mapWithKeys(fn (RawDetection $detection, int $index) => ['D'.($index + 1) => $detection]);
        $valueIds = $detections->flatMap(fn (RawDetection $detection) => $detection->matches->pluck('value_id'))->unique()->values()->all();

        $result = $this->ai->run(new ScoreValuesTask(
            $catalog->forScoring($valueIds),
            $byCode->map(fn (RawDetection $detection, string $code) => [
                'id' => $code,
                'speaker' => $detection->speaker,
                'quote' => $detection->quote,
                'values' => $detection->matches->map(fn ($match) => $catalog->refFor($match->value_id))->filter()->values()->all(),
            ])->values()->all(),
        ), $context);

        foreach ($result->structured['scores'] ?? [] as $row) {
            $value = $catalog->resolve((string) ($row['ref'] ?? ''));

            if ($value === null || ! in_array($value->id, $valueIds, true)) {
                continue;
            }

            $detectionIds = collect($row['detections'] ?? [])
                ->map(fn ($code) => $byCode->get(strtoupper(trim((string) $code)))?->id)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $scored = ($row['state'] ?? null) === ScoreResultState::Scored->value && isset($row['score']) && $detectionIds !== [];

            $material->scores()->create([
                'speaker' => $speaker,
                'value_id' => $value->id,
                'value_version_id' => $value->latestVersion?->id,
                'state' => $scored ? ScoreResultState::Scored : ScoreResultState::NoData,
                'score' => $scored ? max(1, min(6, (int) $row['score'])) : null,
                'reasoning' => isset($row['reasoning']) ? mb_substr(trim((string) $row['reasoning']), 0, 2000) : null,
                'detection_ids' => $detectionIds,
                'ai_call_id' => $result->call->id,
            ]);
        }
    }

    /**
     * Detections with at least one match above the threshold, with their matches.
     *
     * @return Collection<int, RawDetection>
     */
    public function matchedDetections(Material $material, ?string $speaker): Collection
    {
        $threshold = (float) config('megis.engine.match_threshold');

        return $material->detections()
            ->when($speaker !== null, fn ($query) => $query->where('speaker', $speaker))
            ->with(['matches' => fn ($query) => $query->where('confidence', '>=', $threshold)])
            ->orderBy('id')
            ->get()
            ->filter(fn (RawDetection $detection): bool => $detection->matches->isNotEmpty())
            ->values();
    }
}
