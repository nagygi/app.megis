<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Képességmérés, step A (NM-12): verbatim traces of capabilities and
 * preconditions in one part of a material, against the nameless catalog.
 */
final readonly class DetectCapabilityTracesTask implements AiTask
{
    public const SPEAKER_ROLES = ['interviewer', 'leader', 'member', 'external', 'unknown'];

    public const LEVELS = ['egyen', 'csapat', 'szervezet', 'hatarfelulet', 'okoszisztema'];

    public const DIRECTIONS = ['positive', 'negative', 'mixed'];

    public const DIRECTNESS = ['observed', 'self_report', 'others_opinion', 'interpretation'];

    public const CANDIDATES = ['none', 'new_pattern', 'missing_capability'];

    public function __construct(private string $catalog, private string $header, private string $transcript) {}

    public function key(): string
    {
        return 'capability_detection';
    }

    public function variables(): array
    {
        return ['catalog' => $this->catalog];
    }

    public function input(): string
    {
        return $this->header."\n\nSzöveg:\n".$this->transcript;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'traces' => $schema->array()->items($schema->object([
                'quote' => $schema->string()->required(),
                'location' => $schema->string()->required(),
                'speaker' => $schema->string()->required(),
                'speaker_role' => $schema->string()->enum(self::SPEAKER_ROLES)->required(),
                'level' => $schema->string()->enum(self::LEVELS)->required(),
                'direction' => $schema->string()->enum(self::DIRECTIONS)->required(),
                'directness' => $schema->string()->enum(self::DIRECTNESS)->required(),
                'raw' => $schema->boolean()->required(),
                'flags' => $schema->array()->items($schema->string())->required(),
                'capabilities' => $schema->array()->items($schema->string())->required(),
                'preconditions' => $schema->array()->items($schema->object([
                    'code' => $schema->string()->required(),
                    'direction' => $schema->string()->enum(self::DIRECTIONS)->required(),
                ]))->required(),
                'alternative_reading' => $schema->string()->required(),
                'candidate' => $schema->string()->enum(self::CANDIDATES)->required(),
                'candidate_note' => $schema->string()->required(),
            ]))->required(),
        ];
    }
}
