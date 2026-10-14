<?php

namespace App\Models;

use Database\Factories\CalendarConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A connected calendar (09). Access data is stored encrypted; the event
 * metadata lives 90 days and goes at once on disconnecting (09 · 9.5).
 */
#[Fillable([
    'user_id', 'organization_id', 'main_diagnosis_id', 'provider', 'credentials', 'status', 'connected_at',
    'last_synced_at', 'disconnected_at', 'metadata_expires_at', 'error', 'event_count', 'one_off_count', 'unlinked_count',
])]
#[Hidden(['credentials'])]
class CalendarConnection extends Model
{
    /** @use HasFactory<CalendarConnectionFactory> */
    use HasFactory;

    public const GOOGLE = 'google';

    public const MICROSOFT = 'microsoft';

    public const ICS_URL = 'ics_url';

    public const ICS_FILE = 'ics_file';

    public const ACTIVE = 'active';

    public const BROKEN = 'broken';

    public const DISCONNECTED = 'disconnected';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'connected_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'metadata_expires_at' => 'datetime',
            'event_count' => 'integer',
            'one_off_count' => 'integer',
            'unlinked_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<MainDiagnosis, $this>
     */
    public function mainDiagnosis(): BelongsTo
    {
        return $this->belongsTo(MainDiagnosis::class);
    }

    /**
     * @return HasMany<CalendarEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    /**
     * An uploaded file is read once; the others refresh daily (09 · 9.4).
     */
    public function refreshes(): bool
    {
        return $this->provider !== self::ICS_FILE;
    }

    public function providerLabel(): string
    {
        return __('calendar.providers.'.$this->provider);
    }
}
