<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A text of the reading (18 · 18.3): the summary sentence per block, the
 * reading of the brand base and the mégis recommendation. AI draft first.
 */
#[Fillable(['diagnosis_id', 'key', 'text', 'status', 'meta', 'ai_call_id', 'approved_at', 'approved_by'])]
class DiagnosisText extends Model
{
    use Blameable;

    public const KEYS = ['summary_values', 'summary_processes', 'summary_areas', 'summary_brand', 'brand_reading', 'recommendation'];

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
}
