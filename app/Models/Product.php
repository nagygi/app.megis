<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Snapshot · Külső, Snapshot · Belső, Teljes (02 · 2.1). Content and process come later.
 */
#[Fillable(['name', 'description'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Blameable, HasFactory;

    /**
     * @return HasMany<Organization, $this>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}
