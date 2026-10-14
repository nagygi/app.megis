<?php

namespace App\Models;

use App\Enums\ConsentKind;
use App\Enums\ParticipantRole;
use Database\Factories\MainDiagnosisParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The leader or a peer of a main diagnosis (17 · 17.2, 25).
 */
#[Fillable([
    'main_diagnosis_id', 'user_id', 'role', 'excluded_at', 'replaced_at',
    'invited_at', 'opened_at', 'started_at', 'experience_draft', 'personal_value_set_version_id',
])]
class MainDiagnosisParticipant extends Model
{
    /** @use HasFactory<MainDiagnosisParticipantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ParticipantRole::class,
            'excluded_at' => 'datetime',
            'replaced_at' => 'datetime',
            'invited_at' => 'datetime',
            'opened_at' => 'datetime',
            'started_at' => 'datetime',
            'experience_draft' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MainDiagnosis, $this>
     */
    public function mainDiagnosis(): BelongsTo
    {
        return $this->belongsTo(MainDiagnosis::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The peer's current consent of a kind, if given and not withdrawn (25 · 25.2).
     */
    public function consent(ConsentKind $kind): ?Consent
    {
        return Consent::query()
            ->where('user_id', $this->user_id)
            ->where('main_diagnosis_id', $this->main_diagnosis_id)
            ->where('kind', $kind)
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();
    }

    /**
     * A peer whose answers consent was withdrawn is out of the diagnosis (25 · 25.6).
     */
    public function hasWithdrawn(): bool
    {
        return $this->role === ParticipantRole::Peer
            && $this->started_at !== null
            && $this->consent(ConsentKind::Answers) === null;
    }
}
