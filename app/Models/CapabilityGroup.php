<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Database\Factories\CapabilityGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Group of Képességtár capabilities (07 · 7.4), not the Értéktár categories.
 * A capability belongs to exactly one.
 */
#[Fillable(['name', 'name_en', 'description'])]
class CapabilityGroup extends Model
{
    /** @use HasFactory<CapabilityGroupFactory> */
    use Blameable, HasFactory;

    /**
     * @return HasMany<Capability, $this>
     */
    public function capabilities(): HasMany
    {
        return $this->hasMany(Capability::class);
    }

    /**
     * "Kapcsolati intelligencia (Relational Intelligence)" (07, question 3).
     */
    public function label(): string
    {
        return filled($this->name_en) && $this->name_en !== $this->name
            ? $this->name.' ('.$this->name_en.')'
            : $this->name;
    }
}
