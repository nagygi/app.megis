<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Step B: free values matched to the nameless Értéktár catalog by meaning (06 · 6.4).
 */
final readonly class MatchValuesTask implements AiTask
{
    /**
     * @param  list<array{id: string, free_name: string, reasoning: ?string, quote: string}>  $detections
     */
    public function __construct(private string $catalog, private array $detections) {}

    public function key(): string
    {
        return 'mirror_matching';
    }

    public function variables(): array
    {
        return ['catalog' => $this->catalog];
    }

    public function input(): string
    {
        return "Szabad értékek:\n".json_encode($this->detections, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'results' => $schema->array()->items($schema->object([
                'detection' => $schema->string()->required(),
                'matches' => $schema->array()->items($schema->object([
                    'ref' => $schema->string()->required(),
                    'confidence' => $schema->number()->min(0)->max(1)->required(),
                ]))->required(),
            ]))->required(),
        ];
    }
}
