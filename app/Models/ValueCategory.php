<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Database\Factories\ValueCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Category of Értéktár values (03 · 3.4), one of Schwartz's basic values with
 * its higher order value. A value has at most one.
 */
#[Fillable(['name', 'name_en', 'higher_order', 'description'])]
class ValueCategory extends Model
{
    /** @use HasFactory<ValueCategoryFactory> */
    use Blameable, HasFactory;

    /**
     * @return HasMany<Value, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(Value::class);
    }

    /**
     * "Önállóság (Self-direction)".
     */
    public function label(): string
    {
        return filled($this->name_en) && $this->name_en !== $this->name
            ? $this->name.' ('.$this->name_en.')'
            : $this->name;
    }
}
