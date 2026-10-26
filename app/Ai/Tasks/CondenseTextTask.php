<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * A spoken or uploaded text that does not fit its field, condensed to the
 * room left in it, in the writer's own words.
 */
final readonly class CondenseTextTask implements AiTask
{
    public function __construct(
        private string $text,
        private int $maxCharacters,
    ) {}

    public function key(): string
    {
        return 'text_condensing';
    }

    public function variables(): array
    {
        return [];
    }

    public function input(): string
    {
        return json_encode([
            'szöveg' => $this->text,
            'legfeljebb karakter' => $this->maxCharacters,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->required(),
        ];
    }
}
