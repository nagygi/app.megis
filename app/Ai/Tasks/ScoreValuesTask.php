<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Step C: "nincs adat" or 1–6 per matched value, with quote references (06 · 6.4).
 */
final readonly class ScoreValuesTask implements AiTask
{
    /**
     * @param  list<array{id: string, speaker: ?string, quote: string, values: list<string>}>  $quotes
     */
    public function __construct(private string $values, private array $quotes) {}

    public function key(): string
    {
        return 'mirror_scoring';
    }

    public function variables(): array
    {
        return ['values' => $this->values];
    }

    public function input(): string
    {
        return "Idézetek, a hozzájuk illesztett értékek kódjával:\n".json_encode($this->quotes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scores' => $schema->array()->items($schema->object([
                'ref' => $schema->string()->required(),
                'state' => $schema->string()->enum(['scored', 'no_data'])->required(),
                'score' => $schema->integer()->min(1)->max(6)->nullable()->required(),
                'reasoning' => $schema->string()->required(),
                'detections' => $schema->array()->items($schema->string())->required(),
            ]))->required(),
        ];
    }
}
