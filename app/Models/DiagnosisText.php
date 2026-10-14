<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A text of the reading (18 · 18.3): the summary sentence per block, the
 * reading of the brand base and the mégis recommendation. AI draft first.
 */
#[Fillable(['diagnosis_id', 'key', 'text', 'status', 'meta', 'ai_call_id'])]
class DiagnosisText extends Model
{
    use Blameable;

    public const KEYS = ['summary_values', 'summary_processes', 'summary_areas', 'summary_brand', 'brand_reading', 'recommendation'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
