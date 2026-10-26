<?php

namespace App\Services\Mirror;

use App\Enums\InboxItemType;
use App\Enums\MaterialStatus;
use App\Enums\MembershipStatus;
use App\Enums\MirrorStatus;
use App\Enums\OrganizationType;
use App\Enums\TokenPurpose;
use App\Enums\UserRole;
use App\Mail\MirrorBookingMail;
use App\Mail\MirrorConfirmationMail;
use App\Models\AnonymousValueStat;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\LegalAcceptance;
use App\Models\Material;
use App\Models\Membership;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\Product;
use App\Models\RawDetection;
use App\Models\User;
use App\Models\UserToken;
use App\Services\Auth\LandingResolver;
use App\Services\Auth\TokenService;
use App\Services\Engine\AutomaticDiagnosisBuilder;
use App\Services\Inbox\InboxService;
use App\Services\Legal\LegalDocuments;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use App\Services\Organizations\SlugGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * The Értéktükör (06 · 6.1, 6.2): from the recording to the values with
 * quotes, then to an account and a diagnosis. An anonymous mirror lives 24
 * hours; nothing goes to the Értéktár before the account is confirmed (plan 3.7).
 */
class MirrorService
{
    public const EXISTING_ACCOUNT = 'existing';

    public const CONFIRMATION_SENT = 'sent';

    public function __construct(
        private MaterialPipeline $pipeline,
        private MaterialStore $materials,
        private AutomaticDiagnosisBuilder $diagnoses,
        private TokenService $tokens,
        private LegalDocuments $legalDocuments,
        private InboxService $inbox,
        private SlugGenerator $slugs,
        private LandingResolver $landing,
    ) {}

    /**
     * Signed in, the mirror belongs to the user and the active organization
     * from the start (06 · 6.2); anonymous, it expires after 24 hours.
     */
    public function start(Material $material, ?User $user = null, ?Organization $organization = null): MirrorSession
    {
        $session = MirrorSession::create([
            'material_id' => $material->id,
            'status' => MirrorStatus::Processing,
            'user_id' => $user?->id,
            'organization_id' => $organization?->id,
            'claimed_at' => $user === null ? null : now(),
            'expires_at' => $user === null ? now()->addHours((int) config('megis.mirror.anonymous_hours')) : null,
        ]);

        $this->pipeline->start($material);

        return $session;
    }

    /**
     * A failed mirror can go again from what was sent: the recording while
     * it is kept, or the transcript or text already there.
     */
    public function canRetry(MirrorSession $session): bool
    {
        $material = $session->material;

        return $session->status === MirrorStatus::Failed
            && ! ($session->isAnonymous() && $session->isExpired())
            && $material !== null
            && ($material->hasAudio() || filled($material->text));
    }

    /**
     * "Újra próbálom" without sending it again: the work starts where it
     * stopped, at the transcription or at the measurement.
     */
    public function retry(MirrorSession $session): void
    {
        $material = $session->material;

        $session->update(['status' => MirrorStatus::Processing]);
        $material->update(['status' => MaterialStatus::Pending, 'error' => null]);
        $this->inbox->closeAutomatically('processing-error:'.$material->getMorphClass().':'.$material->getKey());

        $this->pipeline->start($material);
    }

    /**
     * The values on ET3, the strongest first; each detection is counted with
     * its best match, or by its free name when nothing matched.
     *
     * @return Collection<int, MirrorValueGroup>
     */
    public function groups(MirrorSession $session): Collection
    {
        $material = $session->material;

        if ($material === null) {
            return collect();
        }

        $threshold = (float) config('megis.engine.match_threshold');

        return $material->detections()
            ->with(['matches' => fn ($query) => $query->where('confidence', '>=', $threshold)->with('value')])
            ->orderBy('id')
            ->get()
            ->groupBy(function (RawDetection $detection): string {
                $match = $detection->matches->first();

                return $match === null ? 'free:'.mb_strtolower($detection->free_name) : 'value:'.$match->value_id;
            })
            ->map(function (Collection $detections, string $key): MirrorValueGroup {
                $first = $detections->first();
                $value = $first->matches->first()?->value;

                return new MirrorValueGroup(
                    key: $key,
                    name: $value?->name ?? $first->free_name,
                    quotes: $detections->pluck('quote')->unique()->values()->all(),
                    detectionIds: $detections->pluck('id')->all(),
                    excluded: $detections->every(fn (RawDetection $detection) => $detection->isExcluded()),
                    valueId: $value?->id,
                );
            })
            ->sortByDesc(fn (MirrorValueGroup $group) => [$group->excluded ? 0 : 1, count($group->quotes)])
            ->values();
    }

    /**
     * "nem az enyém": the value stays out of the diagnosis and stays marked in
     * the raw detection (06 · 6.1, ET3). Tapped again, it comes back.
     */
    public function toggle(MirrorSession $session, string $key): void
    {
        $group = $this->groups($session)->firstWhere('key', $key);

        if ($group === null) {
            return;
        }

        RawDetection::query()->whereIn('id', $group->detectionIds)->update([
            'excluded_at' => $group->excluded ? null : now(),
        ]);

        if ($session->diagnosis !== null) {
            $this->diagnoses->syncRows($session->diagnosis);
        }
    }

    /**
     * ET5 "Létrehozom a fiókot" or "Időpontot foglalok": a confirmation link for
     * a new address; an existing account signs in on the spot instead (06 · 6.1).
     * The mirror's 24 hours start again with the link, so the two expire together.
     *
     * @param  list<string>  $curiosity
     */
    public function requestAccount(MirrorSession $session, string $email, array $curiosity, bool $booking): string
    {
        $email = Str::lower(trim($email));

        $session->update([
            'email' => $email,
            'curiosity' => array_values(array_intersect($curiosity, MirrorSession::CURIOSITY)),
            'accepted_legal_versions' => $this->legalDocuments->currentAcceptable()->pluck('id')->all(),
            'accepted_at' => now(),
        ]);

        if ($booking) {
            $this->recordBooking($session);
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null && $existing->isActivated()) {
            return self::EXISTING_ACCOUNT;
        }

        $user = $existing ?? User::create(['email' => $email]);
        $issued = $this->tokens->issue($user, TokenPurpose::MirrorConfirmation);

        Mail::to($email)->queue(new MirrorConfirmationMail($issued->plainToken));

        $session->update([
            'status' => MirrorStatus::AwaitingConfirmation,
            'expires_at' => $issued->token->expires_at,
        ]);

        return self::CONFIRMATION_SENT;
    }

    /**
     * The mirror waiting for this confirmation: the latest one sent to the address.
     */
    public function sessionForToken(UserToken $token): ?MirrorSession
    {
        return MirrorSession::query()
            ->where('email', $token->user->email)
            ->where('status', MirrorStatus::AwaitingConfirmation)
            ->whereNull('claimed_at')
            ->latest('id')
            ->first();
    }

    /**
     * The short form after the link: name, optional password. The account, a
     * private-person organization led by the user, and the diagnosis are
     * created; the accepted documents are recorded (06 · 6.1).
     */
    public function confirm(UserToken $token, string $firstName, string $lastName, ?string $password): User
    {
        return DB::transaction(function () use ($token, $firstName, $lastName, $password): User {
            $user = $token->user;
            $user->forceFill([
                'first_name' => trim($firstName),
                'last_name' => trim($lastName),
                'password' => filled($password) ? Hash::make($password) : null,
                'activated_at' => now(),
            ])->save();
            $this->tokens->consume($token);

            $organization = $this->createIndividualOrganization($user);
            $session = $this->sessionForToken($token);

            if ($session !== null) {
                foreach ($session->accepted_legal_versions ?? [] as $versionId) {
                    LegalAcceptance::query()->firstOrCreate(
                        ['user_id' => $user->id, 'legal_document_version_id' => $versionId],
                        ['accepted_at' => $session->accepted_at ?? now()],
                    );
                }

                $this->claim($session, $user, $organization);
            }

            return $user;
        });
    }

    /**
     * An existing account signed in on ET5: the mirror goes to its active
     * organization, as from the own menu (06 · 6.1, 6.2).
     */
    public function attachToExisting(MirrorSession $session, User $user): Diagnosis
    {
        return DB::transaction(function () use ($session, $user): Diagnosis {
            $organization = $this->landing->organizationFor($user) ?? $this->createIndividualOrganization($user);

            return $this->claim($session, $user, $organization);
        });
    }

    public function claim(MirrorSession $session, User $user, Organization $organization): Diagnosis
    {
        $material = $session->material;
        $material->update(['user_id' => $user->id, 'organization_id' => $organization->id]);

        $diagnosis = $this->diagnoses->build(
            $material,
            $user,
            $organization,
            DiagnosisType::byKey(DiagnosisType::MIRROR),
            __('mirror.diagnosis_name', ['date' => $session->created_at->format('Y.m.d.')]),
            $session->created_at,
        );

        $session->update([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'diagnosis_id' => $diagnosis->id,
            'status' => MirrorStatus::Claimed,
            'claimed_at' => now(),
            'expires_at' => null,
        ]);

        return $diagnosis;
    }

    /**
     * "Időpontot foglalok": the booking itself is in the external calendar; the
     * click opens a Beérkező row with the ticked questions (22 · 22.2, question 3).
     */
    public function recordBooking(MirrorSession $session): void
    {
        $who = $session->user?->name ?? $session->email ?? __('mirror.someone');
        $curiosity = collect($session->curiosity ?? [])->map(fn (string $key) => __('mirror.curiosity.'.$key.'.short'))->implode(', ');

        $item = $this->inbox->open(
            InboxItemType::MirrorBooking,
            $curiosity === ''
                ? __('mirror.booking_summary', ['name' => $who])
                : __('mirror.booking_summary_curious', ['name' => $who, 'curiosity' => $curiosity]),
            organization: $session->organization,
            subject: $session,
            dedupeKey: 'mirror-booking:'.$session->id,
        );

        if ($item->wasRecentlyCreated) {
            $contact = $session->organization?->contactAdmin;
            $recipients = $contact !== null ? collect([$contact]) : User::query()->where('role', UserRole::Superadmin)->whereNull('deactivated_at')->get();

            foreach ($recipients as $recipient) {
                Mail::to($recipient)->send(new MirrorBookingMail($item));
            }
        }

        $session->update(['booking_requested_at' => now()]);
    }

    /**
     * A private-person organization with the user's name, led by them (06 · 6.1, 02).
     */
    public function createIndividualOrganization(User $user): Organization
    {
        $name = $user->name !== '' ? $user->name : $user->email;
        // One of the starting products (02 · 2.1); created if the seeder has not run.
        $productId = Product::query()->firstOrCreate(['name' => config('megis.individual_product')])->id;

        $organization = Organization::create([
            'type' => OrganizationType::Individual,
            'name' => $name,
            'slug' => $this->slugs->fromName($name),
            'product_id' => $productId,
        ]);

        Membership::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => MembershipStatus::Active,
            'is_organization_leader' => true,
        ]);

        return $organization;
    }

    /**
     * After 24 hours an unclaimed mirror goes; only the free name and the match
     * stay, without the quote (06 · 6.1). An account started for it and never
     * confirmed goes with it.
     */
    public function pruneExpired(): int
    {
        $count = 0;
        $threshold = (float) config('megis.engine.match_threshold');

        MirrorSession::query()
            ->whereNull('claimed_at')
            ->where('expires_at', '<=', now())
            ->with('material.detections.matches')
            ->each(function (MirrorSession $session) use (&$count, $threshold): void {
                DB::transaction(function () use ($session, $threshold): void {
                    if ($session->material !== null) {
                        foreach ($session->material->detections as $detection) {
                            // Only a real match counts, the same threshold as everywhere (06 · 6.4).
                            $best = $detection->matches->first(fn ($match) => $match->confidence >= $threshold);

                            AnonymousValueStat::create([
                                'free_name' => $detection->free_name,
                                'value_id' => $best?->value_id,
                                'confidence' => $best?->confidence,
                                'recorded_on' => $session->created_at->toDateString(),
                            ]);
                        }

                        $this->materials->delete($session->material);
                    }

                    $this->deletePendingAccount($session);
                    $session->delete();
                });

                $count++;
            });

        return $count;
    }

    /**
     * A newer mirror may still wait on the same address; its link needs the account.
     */
    private function deletePendingAccount(MirrorSession $session): void
    {
        if ($session->email === null) {
            return;
        }

        $stillWaiting = MirrorSession::query()
            ->whereKeyNot($session->id)
            ->where('email', $session->email)
            ->whereNull('claimed_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($stillWaiting) {
            return;
        }

        $user = User::query()->where('email', $session->email)->whereNull('activated_at')->whereNull('role')->first();

        if ($user !== null && ! $user->memberships()->exists()) {
            $user->delete();
        }
    }
}
