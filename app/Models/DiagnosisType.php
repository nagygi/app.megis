<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Database\Factories\DiagnosisTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Type of a diagnosis (04 · 4.1). Types with a key are referred to by the code.
 */
#[Fillable(['name', 'description'])]
class DiagnosisType extends Model
{
    /** @use HasFactory<DiagnosisTypeFactory> */
    use Blameable, HasFactory;

    public const MAIN = 'main';

    public const EXPERIENCE = 'experience';

    public const PROCESS = 'process';

    public const MIRROR = 'mirror';

    public static function byKey(string $key): self
    {
        return static::query()->where('key', $key)->firstOrFail();
    }

    /**
     * @return HasMany<Diagnosis, $this>
     */
    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    /**
     * A Megélés diagnosis has only self-reported experience, no score (08 · 8.3).
     */
    public function isExperience(): bool
    {
        return $this->key === self::EXPERIENCE;
    }

    public function isSystemType(): bool
    {
        return $this->key !== null;
    }
}
