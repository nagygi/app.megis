<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Step A: free value detection, without any value list (06 · 6.4). The
 * situation names the process of an occasion's material (13 · 13.2).
 */
final readonly class DetectValuesTask implements AiTask
{
    public function __construct(private string $transcript, private string $situation = '') {}

    public function key(): string
    {
        return 'mirror_detection';
    }

    public function variables(): array
    {
        return ['context' => $this->situation];
    }

    public function input(): string
    {
        return $this->transcript;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'detections' => $schema->array()->items($schema->object([
                'speaker' => $schema->string()->required(),
                'quote' => $schema->string()->required(),
                'free_name' => $schema->string()->required(),
                'reasoning' => $schema->string()->required(),
                'strength' => $schema->integer()->min(1)->max(6)->required(),
            ]))->required(),
        ];
    }
}
