<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A text of the reading (18 · 18.3): the summary sentence per block, the
 * reading of the brand base and the mégis recommendation; per process row
 * the "Mit nézünk ebben a rutinban" sentence (18 · 18.2, R3·b). AI draft first.
 */
#[Fillable(['diagnosis_id', 'key', 'text', 'status', 'meta', 'ai_call_id', 'approved_at', 'approved_by'])]
class DiagnosisText extends Model
{
    use Blameable;

    public const KEYS = ['summary_values', 'summary_processes', 'summary_areas', 'summary_brand', 'brand_reading', 'recommendation'];

    /**
     * The key of a process row's sentence: the prefix and the row id.
     */
    public const PROCESS_KEY_PREFIX = 'what_we_look_at_';

    public static function processKey(int $rowId): string
    {
        return self::PROCESS_KEY_PREFIX.$rowId;
    }

    /**
     * The process row of a key, or null for the fixed keys.
     */
    public static function processRowId(string $key): ?int
    {
        return str_starts_with($key, self::PROCESS_KEY_PREFIX) && ctype_digit($id = substr($key, strlen(self::PROCESS_KEY_PREFIX))) ? (int) $id : null;
    }

    /**
     * The longest text per key; the report is read on a phone (20, question 4).
     */
    public const MAX_LENGTH = ['brand_reading' => 600];

    public const DEFAULT_MAX_LENGTH = 160;

    public static function maxLength(string $key): int
    {
        return self::MAX_LENGTH[$key] ?? self::DEFAULT_MAX_LENGTH;
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
