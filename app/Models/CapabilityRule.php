<?php

namespace App\Models;

use App\Enums\CapabilityRuleArea;
use App\Enums\CapabilityRuleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A rule of the mégis Szabálykönyv (NY-01, KO-03 …), loaded with the tár and
 * read only. The measurement cites the codes of the rules it applies.
 */
#[Fillable(['code', 'area', 'text', 'status', 'status_label', 'source', 'version', 'decided_on', 'imported_at'])]
class CapabilityRule extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area' => CapabilityRuleArea::class,
            'status' => CapabilityRuleStatus::class,
            'imported_at' => 'datetime',
        ];
    }
}
