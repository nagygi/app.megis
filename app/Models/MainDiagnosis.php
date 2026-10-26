<?php

namespace App\Models;

use App\Enums\DiagnosisStep;
use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Enums\ParticipantRole;
use App\Models\Concerns\Blameable;
use Carbon\CarbonInterface;
use Database\Factories\MainDiagnosisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A 04 diagnosis with what it needs to run (17): subject, product snapshot,
 * leader, peers, time window and stage. The diagnoses made while it runs are
 * its subdiagnoses (17 · 17.4).
 */
#[Fillable([
    'diagnosis_id', 'subject', 'product_id', 'product_key', 'product_name', 'leader_id', 'stage',
    'starts_on', 'publishes_on', 'show_peer_quotes', 'value_set_version_id',
    'threshold_overridden_at', 'threshold_overridden_by', 'threshold_snoozed_until', 'calculation', 'calculated_at',
    'biggest_gap_row_id', 'biggest_gap_approved_at', 'biggest_gap_approved_by',
])]
class MainDiagnosis extends Model
{
    /** @use HasFactory<MainDiagnosisFactory> */
    use Blameable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject' => MainDiagnosisSubject::class,
            'stage' => MainDiagnosisStage::class,
            'starts_on' => 'date',
            'publishes_on' => 'date',
            'show_peer_quotes' => 'boolean',
            'invited_at' => 'datetime',
            'running_at' => 'datetime',
            'reading_at' => 'datetime',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
            'threshold_overridden_at' => 'datetime',
            'threshold_snoozed_until' => 'date',
            'biggest_gap_approved_at' => 'datetime',
            'calculation' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Diagnosis, $this>
     */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Version, $this>
     */
    public function valueSetVersion(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'value_set_version_id');
    }

    /**
     * @return HasMany<MainDiagnosisParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(MainDiagnosisParticipant::class);
    }

    /**
     * Peers who take part: not taken off the list on A1.
     *
     * @return HasMany<MainDiagnosisParticipant, $this>
     */
    public function peers(): HasMany
    {
        return $this->participants()->where('role', ParticipantRole::Peer)->whereNull('excluded_at');
    }

    /**
     * The processes the diagnosis measures (11 · 11.4).
     *
     * @return BelongsToMany<Process, $this>
     */
    public function processes(): BelongsToMany
    {
        return $this->belongsToMany(Process::class, 'main_diagnosis_processes')->withTimestamps();
    }

    /**
     * @return HasMany<MegisConversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(MegisConversation::class)->orderBy('held_at');
    }

    /**
     * @return HasMany<ReminderDispatch, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(ReminderDispatch::class);
    }

    /**
     * @return HasMany<MainDiagnosisStepMark, $this>
     */
    public function stepMarks(): HasMany
    {
        return $this->hasMany(MainDiagnosisStepMark::class);
    }

    /**
     * @return HasMany<CapabilityStory, $this>
     */
    public function capabilityStories(): HasMany
    {
        return $this->hasMany(CapabilityStory::class);
    }

    /**
     * @return HasMany<Consent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    #[Scope]
    protected function inStages(Builder $query, MainDiagnosisStage ...$stages): void
    {
        $query->whereIn('stage', array_map(fn (MainDiagnosisStage $stage) => $stage->value, $stages));
    }

    /**
     * From the invitation until reading starts (17 · 17.4).
     */
    #[Scope]
    protected function acceptingInput(Builder $query): void
    {
        $query->inStages(MainDiagnosisStage::Invited, MainDiagnosisStage::Filling, MainDiagnosisStage::Running);
    }

    /**
     * Diagnoses the user takes part in: as the current leader, or as a peer still on the list.
     */
    #[Scope]
    protected function withParticipant(Builder $query, User $user, ?ParticipantRole $role = null): void
    {
        $query->whereHas('participants', fn (Builder $participants) => $participants
            ->where('user_id', $user->id)
            ->whereNull('excluded_at')
            ->whereNull('replaced_at')
            ->when($role !== null, fn (Builder $byRole) => $byRole->where('role', $role)));
    }

    /**
     * The settings of the product at the start (config/diagnosis.php).
     *
     * @return array{days: int, has_peers: bool, max_measured_processes: ?int}
     */
    public function productSettings(): array
    {
        return config('diagnosis.products.'.$this->product_key);
    }

    public function isSnapshot(): bool
    {
        return $this->product_key === Product::SNAPSHOT_INTERNAL;
    }

    /**
     * Peers only in the Teljes product and only for a team (17 · 17.3, 25).
     */
    public function hasPeers(): bool
    {
        return $this->subject === MainDiagnosisSubject::Team && $this->productSettings()['has_peers'];
    }

    /**
     * The leader's steps in the order of V1 (17 · 17.3).
     *
     * @return list<DiagnosisStep>
     */
    public function leaderSteps(): array
    {
        return self::stepsFor($this->subject, $this->product_key);
    }

    /**
     * @return list<DiagnosisStep>
     */
    public function peerSteps(): array
    {
        return self::peerStepsFor($this->product_key);
    }

    /**
     * A peer's steps in a product; the organization home previews the same (26 · 26.4).
     *
     * @return list<DiagnosisStep>
     */
    public static function peerStepsFor(?string $productKey): array
    {
        return self::filterSteps(config('diagnosis.peer_steps'), $productKey);
    }

    /**
     * The leader's steps of a subject and product; the A1 preview reads the same (17 · 17.3).
     *
     * @return list<DiagnosisStep>
     */
    public static function stepsFor(MainDiagnosisSubject $subject, ?string $productKey): array
    {
        return self::filterSteps(config('diagnosis.steps.'.$subject->value), $productKey);
    }

    /**
     * Whether the capability measurement reads the materials of this product (NM-12):
     * the global switch and the product's own.
     */
    public static function measuresCapabilitiesFor(?string $productKey): bool
    {
        return (bool) config('megis.capabilities.measurement.enabled')
            && (bool) config('diagnosis.products.'.$productKey.'.capability_measurement', false);
    }

    public function measuresCapabilities(): bool
    {
        return self::measuresCapabilitiesFor($this->product_key);
    }

    /**
     * The minutes in the peer invitation and on T1 (25 · 25.2), with Képességek when it is on.
     */
    public function peerMinutes(): int
    {
        return (int) config('diagnosis.peer_minutes')
            + ($this->measuresCapabilities() ? DiagnosisStep::Capabilities->minutes() : 0);
    }

    /**
     * @param  list<string>  $keys
     * @return list<DiagnosisStep>
     */
    private static function filterSteps(array $keys, ?string $productKey): array
    {
        $steps = array_map(fn (string $step) => DiagnosisStep::from($step), $keys);

        return array_values(array_filter(
            $steps,
            fn (DiagnosisStep $step) => $step !== DiagnosisStep::Capabilities || self::measuresCapabilitiesFor($productKey),
        ));
    }

    /**
     * The participant row of the current leader.
     */
    public function leaderParticipant(): ?MainDiagnosisParticipant
    {
        return $this->participants
            ->first(fn (MainDiagnosisParticipant $participant) => $participant->role === ParticipantRole::Leader
                && $participant->user_id === $this->leader_id
                && $participant->replaced_at === null);
    }

    public function participantFor(User $user, ParticipantRole $role): ?MainDiagnosisParticipant
    {
        return $this->participants()
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->whereNull('excluded_at')
            ->whereNull('replaced_at')
            ->first();
    }

    public function isLeader(User $user): bool
    {
        return $this->leader_id === $user->id;
    }

    /**
     * "23. nap / 47" (19 · 19.1): from the start (or the invitation) to publishing.
     *
     * @return array{day: int, total: int}|null
     */
    public function dayCount(): ?array
    {
        $start = ($this->starts_on ?? $this->invited_at)?->copy()->startOfDay();

        if ($start === null || $this->publishes_on === null) {
            return null;
        }

        $total = max(1, (int) $start->diffInDays($this->publishes_on) + 1);

        return ['day' => max(0, min($total, (int) $start->diffInDays(today(), false) + 1)), 'total' => $total];
    }

    /**
     * The tab of the stage, where the main diagnosis opens (19 · 19.1).
     */
    public function stageRoute(?User $admin = null): string
    {
        return match (true) {
            $this->stage->isBeforeRunning() => 'admin.main-diagnoses.edit',
            $this->stage === MainDiagnosisStage::Running && $admin?->can('run', $this->diagnosis) => 'admin.main-diagnoses.run',
            $this->stage === MainDiagnosisStage::Reading => 'admin.main-diagnoses.reading',
            $this->stage === MainDiagnosisStage::Published => 'admin.main-diagnoses.publish',
            default => 'admin.diagnoses.show',
        };
    }

    /**
     * Reading starts this many days before publishing (17 · 17.2).
     */
    public function readingStartsOn(): CarbonInterface
    {
        return $this->publishes_on->copy()->subDays((int) config('diagnosis.reading_offset_days'));
    }

    /**
     * "Value · Core Team", the person's name, or the private person's organization.
     */
    public function subjectName(): string
    {
        $diagnosis = $this->diagnosis;

        return match ($this->subject) {
            MainDiagnosisSubject::Team => (string) $diagnosis->team?->name,
            MainDiagnosisSubject::Person => (string) $diagnosis->user?->name,
            MainDiagnosisSubject::Individual => (string) $diagnosis->organization->name,
        };
    }

    /**
     * Saving the own values stays open until publishing; everything that goes
     * into the calculation closes when reading starts (plan 15 · 2).
     */
    public function acceptsInput(): bool
    {
        return $this->stage->acceptsInput();
    }

    public function ownValuesEditable(): bool
    {
        return ! in_array($this->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true)
            && $this->stage !== MainDiagnosisStage::Draft;
    }
}
