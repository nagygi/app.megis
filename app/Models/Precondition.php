<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One of the fixed preconditions E1–E9 (07 · 7.6): a structural condition
 * without which a capability does not work, even when people can do it.
 * Read only, it comes with the schema.
 */
class Precondition extends Model
{
    public function label(): string
    {
        return $this->code.' · '.$this->name;
    }
}
