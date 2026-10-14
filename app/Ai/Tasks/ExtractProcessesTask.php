<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * The live extract of one answer of the conversation (10 · 10.2): the
 * processes heard in it, and what sounds like a pattern rather than an occasion.
 */
final readonly class ExtractProcessesTask implements AiTask
{
    public function __construct(
        private string $question,
        private string $answer,
    ) {}

    public function key(): string
    {
        return 'conversation_summary';
    }

    public function variables(): array
    {
        return [];
    }

    public function input(): string
    {
        return "Kérdés: {$this->question}\n\nVálasz:\n{$this->answer}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'processes' => $schema->array()->items($schema->object([
                'name' => $schema->string()->required(),
                'kind' => $schema->string()->enum(['process', 'unclear'])->required(),
                // weekly · biweekly · monthly · quarterly · occasional, or null; checked when stored.
                'rhythm' => $schema->string()->nullable()->required(),
                'rhythm_label' => $schema->string()->nullable()->required(),
                'headcount' => $schema->integer()->min(1)->nullable()->required(),
            ]))->required(),
        ];
    }
}
