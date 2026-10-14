<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A reminder sent to one person; one recipient gets at most one per 48 hours (19 · 19.4).
 */
#[Fillable(['main_diagnosis_id', 'user_id', 'kind', 'sent_by'])]
class ReminderDispatch extends Model
{
    public const UPDATED_AT = null;

    public const LEADER = 'leader';

    public const PEER = 'peer';

    public const THRESHOLD = 'threshold';

    public const WINDOW = 'window';
}
