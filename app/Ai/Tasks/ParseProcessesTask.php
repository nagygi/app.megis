<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Several processes at once from a written or spoken text (11 · 11.2, 11.6):
 * name, description, rhythm, and the parent among the new or the existing ones.
 */
final readonly class ParseProcessesTask implements AiTask
{
    /**
     * @param  list<array{ref: string, name: string}>  $existing  the organization's top-level processes
     */
    public function __construct(
        private string $text,
        private array $existing = [],
    ) {}

    public function key(): string
    {
        return 'process_parsing';
    }

    public function variables(): array
    {
        return [];
    }

    public function input(): string
    {
        return json_encode([
            'szöveg' => $this->text,
            'meglévő folyamatok' => $this->existing,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'processes' => $schema->array()->items($schema->object([
                'ref' => $schema->string()->required(),
                'name' => $schema->string()->required(),
                'description' => $schema->string()->nullable()->required(),
                // weekly · biweekly · monthly · quarterly · occasional, or null; checked when read.
                'rhythm' => $schema->string()->nullable()->required(),
                'parent_ref' => $schema->string()->nullable()->required(),
            ]))->required(),
        ];
    }
}
