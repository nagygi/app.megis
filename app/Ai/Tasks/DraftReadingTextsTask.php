<?php

namespace App\Ai\Tasks;

use App\Ai\Contracts\AiTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * The texts of the reading (18 · 18.3): one summary sentence per block, the
 * reading of the brand base, the mégis recommendation with the measuring
 * point it would move, and per process row what we look at in it (18 · 18.2).
 * The AI gets the rows and the claims, never names.
 */
final readonly class DraftReadingTextsTask implements AiTask
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $claims
     */
    public function __construct(
        private array $rows,
        private array $claims,
        private bool $withAreas,
    ) {}

    public function key(): string
    {
        return 'reading_texts';
    }

    public function variables(): array
    {
        return [
            'areas' => $this->withAreas ? 'van' : 'nincs (Belső snapshot)',
        ];
    }

    public function input(): string
    {
        return json_encode(['sorok' => $this->rows, 'állítások' => $this->claims], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary_values' => $schema->string()->required(),
            'summary_processes' => $schema->string()->required(),
            'summary_areas' => $schema->string()->nullable()->required(),
            'summary_brand' => $schema->string()->required(),
            'brand_reading' => $schema->string()->required(),
            'recommendation' => $schema->object([
                'text' => $schema->string()->required(),
                'row_ref' => $schema->string()->nullable()->required(),
                'from' => $schema->integer()->nullable()->required(),
                'to' => $schema->integer()->nullable()->required(),
            ])->required(),
            // "Mit nézünk ebben a rutinban" per process row (18 · 18.2, R3·b).
            'what_we_look_at' => $schema->array()->items($schema->object([
                'row_ref' => $schema->string()->required(),
                'text' => $schema->string()->required(),
            ]))->required(),
        ];
    }
}
