<?php

namespace App\Ai\Agents;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

/**
 * A task agent whose answer follows the task's schema.
 */
class StructuredTaskAgent extends TaskAgent implements HasStructuredOutput
{
    /**
     * @param  Closure(JsonSchema): array<string, mixed>  $schema
     */
    public function __construct(
        string $prompt,
        protected Closure $schema,
        ?int $maxTokens = null,
        ?float $temperature = null,
    ) {
        parent::__construct($prompt, $maxTokens, $temperature);
    }

    public function schema(JsonSchema $schema): array
    {
        return ($this->schema)($schema);
    }
}
