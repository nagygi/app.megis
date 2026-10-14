<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Snapshot · Külső, Snapshot · Belső, Teljes (02 · 2.1). The key of a
 * starting product chooses its diagnosis flow (17 · 17.3); it is set by the
 * schema and the seeder, never by the form.
 */
#[Fillable(['name', 'description'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Blameable, HasFactory;

    public const SNAPSHOT_EXTERNAL = 'snapshot_external';

    public const SNAPSHOT_INTERNAL = 'snapshot_internal';

    public const FULL = 'full';

    /**
     * @return HasMany<Organization, $this>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * Snapshot · Külső and new products have no diagnosis flow yet (17 · 17.2).
     */
    public function hasDiagnosisFlow(): bool
    {
        return $this->key !== null && config('diagnosis.products.'.$this->key) !== null;
    }
}
