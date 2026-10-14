<?php

namespace Tests\Fixtures\Ai;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * A task for the AI layer tests; its config and prompt are set by the test.
 */
class TestTask implements AiTask
{
    public function __construct(
        private string $input = 'Egy rövid átirat.',
        private bool $structured = false,
    ) {}

    public function key(): string
    {
        return 'test_task';
    }

    public function variables(): array
    {
        return ['organization' => 'Acme Kft.'];
    }

    public function input(): string
    {
        return $this->input;
    }

    public function schema(JsonSchema $schema): ?array
    {
        return $this->structured
            ? ['values' => $schema->array()->items($schema->string())->required()]
            : null;
    }
}
