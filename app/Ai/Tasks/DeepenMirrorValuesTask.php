<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * ET4: the mégis Coach puts each value of a mirror into the user's own words,
 * from their quotes: what it means, where it shows, the 1 and the 6 (NM-06).
 */
final readonly class DeepenMirrorValuesTask implements AiTask
{
    /**
     * @param  list<array{ref: string, name: string, quotes: list<string>, reasoning: list<string>, library: ?string}>  $values
     */
    public function __construct(private array $values) {}

    public function key(): string
    {
        return 'mirror_deepening';
    }

    public function variables(): array
    {
        return [];
    }

    public function input(): string
    {
        return "Az értékek, a kitöltő idézeteivel:\n".json_encode($this->values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'values' => $schema->array()->items($schema->object([
                'ref' => $schema->string()->required(),
                'meaning' => $schema->string()->required(),
                'signs' => $schema->array()->items($schema->string())->required(),
                'low' => $schema->string()->required(),
                'high' => $schema->string()->required(),
            ]))->required(),
        ];
    }
}
