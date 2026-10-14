<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use App\Models\DiagnosisClaim;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Claims about the rows of a main diagnosis (18 · 18.3): strength, crack
 * ("töréspont" in a snapshot) or hunch, each on one row and built on its
 * traces. The AI gets the rows and the quotes with role and date, never names.
 */
final readonly class DraftClaimsTask implements AiTask
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        private array $rows,
        private bool $snapshot,
    ) {}

    public function key(): string
    {
        return 'diagnosis_claims';
    }

    public function variables(): array
    {
        return [
            'crack' => $this->snapshot ? 'töréspont' : 'repedés',
        ];
    }

    public function input(): string
    {
        return json_encode(['sorok' => $this->rows], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'claims' => $schema->array()->items($schema->object([
                'row_ref' => $schema->string()->required(),
                'kind' => $schema->string()->enum(DiagnosisClaim::KINDS)->required(),
                'text' => $schema->string()->required(),
                'trace_refs' => $schema->array()->items($schema->string())->required(),
            ]))->required(),
        ];
    }
}
