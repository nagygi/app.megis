<?php

namespace App\Models;

use App\Enums\LegalDocumentType;
use App\Models\Concerns\Blameable;
use Database\Factories\LegalDocumentVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An uploaded PDF version of a legal document (05). Never deleted.
 */
#[Fillable(['type', 'file_path', 'original_name', 'effective_from', 'requires_reacceptance'])]
class LegalDocumentVersion extends Model
{
    /** @use HasFactory<LegalDocumentVersionFactory> */
    use Blameable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LegalDocumentType::class,
            'effective_from' => 'date',
            'requires_reacceptance' => 'boolean',
        ];
    }

    /**
     * Versions already in force; a future-dated upload waits for its date (05 · 5.1).
     */
    #[Scope]
    protected function inForce(Builder $query): void
    {
        $query->whereDate('effective_from', '<=', today());
    }

    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->orderByDesc('effective_from')->orderByDesc('id');
    }

    public static function currentFor(LegalDocumentType $type): ?self
    {
        return static::query()->where('type', $type)->inForce()->newestFirst()->first();
    }
}
