<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Képességmérés, step B (NM-12): the traces of one capability matched to its
 * patterns, with the detector-error patterns as prohibitions.
 */
final readonly class MatchCapabilityPatternsTask implements AiTask
{
    /**
     * @param  list<array<string, mixed>>  $traces
     */
    public function __construct(
        private string $capability,
        private string $patterns,
        private string $detectors,
        private array $traces,
    ) {}

    public function key(): string
    {
        return 'capability_matching';
    }

    public function variables(): array
    {
        return ['capability' => $this->capability, 'patterns' => $this->patterns, 'detectors' => $this->detectors];
    }

    public function input(): string
    {
        return "Nyomok:\n".json_encode($this->traces, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'findings' => $schema->array()->items($schema->object([
                'pattern' => $schema->string()->required(),
                'traces' => $schema->array()->items($schema->string())->required(),
                'level' => $schema->string()->enum(DetectCapabilityTracesTask::LEVELS)->required(),
                'confidence' => $schema->string()->enum(['low', 'medium'])->required(),
                'reasoning' => $schema->string()->required(),
            ]))->required(),
            'blocked' => $schema->array()->items($schema->object([
                'trace' => $schema->string()->required(),
                'detector' => $schema->string()->required(),
                'reason' => $schema->string()->required(),
            ]))->required(),
        ];
    }
}
