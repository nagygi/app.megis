<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Names, types and subprocesses for the calendar's patterns (09 · 9.3). The
 * AI gets titles, descriptions, rhythm and headcount, never names or e-mail
 * addresses; it may point to a process from the conversation (10 · 10.4).
 */
final readonly class NameProcessesTask implements AiTask
{
    /**
     * @param  list<array{ref: string, title: string, description: ?string, rhythm: ?string, headcount: ?int}>  $candidates
     * @param  list<array{ref: string, name: string, rhythm: ?string}>  $conversationProcesses
     */
    public function __construct(
        private array $candidates,
        private array $conversationProcesses = [],
    ) {}

    public function key(): string
    {
        return 'calendar_naming';
    }

    public function variables(): array
    {
        return [];
    }

    public function input(): string
    {
        return json_encode([
            'minták' => $this->candidates,
            'beszélgetésből' => $this->conversationProcesses,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'suggestions' => $schema->array()->items($schema->object([
                'ref' => $schema->string()->required(),
                'name' => $schema->string()->required(),
                'kind' => $schema->string()->enum(['routine', 'one_off'])->required(),
                'parent_ref' => $schema->string()->nullable()->required(),
                'merge_with' => $schema->string()->nullable()->required(),
            ]))->required(),
        ];
    }
}
