<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The metadata of one calendar event (09 · 9.1): kept 90 days at most.
 */
#[Fillable(['calendar_connection_id', 'external_id', 'series_key', 'title', 'description', 'starts_at', 'ends_at', 'attendees'])]
class CalendarEvent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'attendees' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CalendarConnection, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(CalendarConnection::class, 'calendar_connection_id');
    }

    public function durationMinutes(): ?int
    {
        return $this->ends_at === null ? null : (int) round($this->starts_at->diffInMinutes($this->ends_at));
    }
}
