<?php

namespace App\Models;

use App\Enums\MaterialContext;
use App\Enums\MaterialSource;
use App\Enums\MaterialStatus;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A recording, transcript or text the value measurement reads (06). The
 * audio stays on the private disk until its owner or an admin deletes it;
 * the transcript stays with it (06 · 6.6).
 */
#[Fillable([
    'context', 'source', 'organization_id', 'user_id', 'original_name',
    'audio_path', 'audio_mime', 'audio_seconds', 'text', 'segments', 'speaker_assignments', 'diagnosis_type_id', 'diagnosed_on',
    'status', 'error', 'transcription_ai_call_id', 'processed_at', 'created_by', 'parent_diagnosis_id',
])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    public const DISK = 'materials';

    protected static function booted(): void
    {
        static::creating(function (Material $material): void {
            $material->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => MaterialContext::class,
            'source' => MaterialSource::class,
            'status' => MaterialStatus::class,
            'audio_seconds' => 'integer',
            'audio_deleted_at' => 'datetime',
            'segments' => 'array',
            'speaker_assignments' => 'array',
            'diagnosed_on' => 'date',
            'processed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<RawDetection, $this>
     */
    public function detections(): HasMany
    {
        return $this->hasMany(RawDetection::class);
    }

    /**
     * @return HasMany<MaterialValueScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(MaterialValueScore::class);
    }

    /**
     * @return HasMany<Diagnosis, $this>
     */
    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function hasAudio(): bool
    {
        return $this->audio_path !== null && $this->audio_deleted_at === null;
    }

    /**
     * The speakers of the transcript in order of first appearance.
     *
     * @return list<string>
     */
    public function speakers(): array
    {
        return array_values(array_unique(array_map(
            fn (array $segment): string => (string) ($segment['speaker'] ?? ''),
            $this->segments ?? [],
        )));
    }

    /**
     * The transcript as the engine reads it: one turn per line, "Speaker: text".
     */
    public function transcriptForEngine(): string
    {
        if (($this->segments ?? []) === []) {
            return (string) $this->text;
        }

        return implode("\n", array_map(
            fn (array $segment): string => trim(($segment['speaker'] ?? '').': '.($segment['text'] ?? '')),
            $this->segments,
        ));
    }

    /**
     * "elmondva · 0:42", "leírva", "feltöltve" (06, 12 · 12.3).
     */
    public function durationLabel(): ?string
    {
        if ($this->audio_seconds === null) {
            return null;
        }

        return intdiv($this->audio_seconds, 60).':'.str_pad((string) ($this->audio_seconds % 60), 2, '0', STR_PAD_LEFT);
    }
}
