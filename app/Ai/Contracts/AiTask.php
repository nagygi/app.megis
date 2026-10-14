<?php

namespace App\Ai\Contracts;

use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * One run of an AI task (16 · 16.1). The task's key selects the model and
 * the prompt from config/ai.php; the feature only provides the input.
 */
interface AiTask
{
    /**
     * Key in config('ai.tasks').
     */
    public function key(): string;

    /**
     * Values for the {{ placeholders }} of the prompt.
     *
     * @return array<string, string>
     */
    public function variables(): array;

    /**
     * The message the model answers, e.g. a transcript.
     */
    public function input(): string;

    /**
     * Shape of a structured answer, or null for plain text.
     *
     * @return array<string, mixed>|null
     */
    public function schema(JsonSchema $schema): ?array;
}
