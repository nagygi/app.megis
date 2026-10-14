<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One of the five areas of a Teljes diagnosis (18 · 18.1), scored by hand.
 * The texts are edited in Beállítások → Területek; the report and the
 * Bölcsességtár read them.
 */
#[Fillable(['name', 'what_we_look_at', 'trace', 'six_description', 'position'])]
class Area extends Model
{
    use Blameable;
}
