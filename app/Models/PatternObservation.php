<?php

namespace App\Models;

use App\Enums\FindingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A decision on a finding of a pattern, without the client (NY-27): the
 * organization is only a keyed hash, so the pattern's counters survive the
 * deletion of a client's data.
 */
#[Fillable(['pattern_id', 'organization_key', 'outcome', 'observed_on'])]
class PatternObservation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outcome' => FindingStatus::class,
            'observed_on' => 'date',
        ];
    }

    public static function keyFor(Organization $organization): string
    {
        return hash_hmac('sha256', 'organization:'.$organization->id, (string) config('app.key'));
    }
}
