<?php

namespace App\Services\ExternalClients;

use App\Enums\AuditEventType;
use App\Enums\ConsentKind;
use App\Enums\ExternalClientItemKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\TokenPurpose;
use App\Mail\ExternalClientInvitationMail;
use App\Models\Consent;
use App\Models\ExternalClient;
use App\Models\ExternalClientAnswer;
use App\Models\ExternalClientItem;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\TokenService;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Ügyfélmérés in a diagnosis (35 · 35.4–35.6): which clients are asked, their
 * users' invitations, consent, drafts and the answers sent. A sent answer is
 * a material of its own; it belongs to the diagnosis through the answer, so
 * the team's numbers and views do not change.
 */
class ExternalClientMeasurementService
{
    public function __construct(
        private TokenService $tokens,
        private MaterialStore $materials,
        private MaterialPipeline $pipeline,
        private AuditLogger $audit,
    ) {}

    /**
     * The clients A1 offers: the active ones, when Ügyfélmérés is on (35 · 35.4).
     *
     * @return Collection<int, ExternalClient>
     */
    public function selectableClients(?Organization $organization): Collection
    {
        if ($organization === null || ! $organization->usesExternalClients()) {
            return collect();
        }

        return $organization->externalClients()->active()->orderBy('name')->get();
    }

    /**
     * A1: the clients of the diagnosis. One added while it runs is invited at once.
     *
     * @param  list<int>  $clientIds
     */
    public function syncClients(MainDiagnosis $main, array $clientIds, ?User $actor = null): void
    {
        $organization = $main->diagnosis->organization;
        $allowed = $organization->usesExternalClients()
            ? $organization->externalClients()->whereIn('id', $clientIds)->pluck('id')->all()
            : [];

        $main->externalClients()->sync($allowed);

        if ($main->stage === MainDiagnosisStage::Running) {
            $this->inviteClients($main, $actor);
        }
    }

    /**
     * As the diagnosis starts running, every client's users get their link
     * (35 · 35.5); a client already invited is not invited again.
     */
    public function inviteClients(MainDiagnosis $main, ?User $actor = null): void
    {
        if (! $main->diagnosis->organization->usesExternalClients()) {
            return;
        }

        $clients = $main->externalClients()->active()->wherePivotNull('invited_at')->with('users', 'organization')->get();

        foreach ($clients as $client) {
            foreach ($client->users as $user) {
                $this->inviteUser($main, $client, $user, $actor);
            }

            $main->externalClients()->updateExistingPivot($client->id, ['invited_at' => now()]);
        }
    }

    /**
     * Users put to a client while its diagnoses run get their link at once.
     *
     * @param  list<int>  $userIds
     */
    public function usersAdded(ExternalClient $client, array $userIds, ?User $actor = null): void
    {
        if ($userIds === [] || ! $client->isActive() || ! $client->organization->usesExternalClients()) {
            return;
        }

        $mains = $client->mainDiagnoses()->where('stage', MainDiagnosisStage::Running)->wherePivotNotNull('invited_at')->get();
        $users = User::query()->whereIn('id', $userIds)->get();

        foreach ($mains as $main) {
            foreach ($users as $user) {
                $this->inviteUser($main, $client, $user, $actor);
            }
        }
    }

    /**
     * A personal link valid for 14 days; a new one takes the place of the old.
     */
    public function inviteUser(MainDiagnosis $main, ExternalClient $client, User $user, ?User $actor = null): void
    {
        if (! $user->isActive() || $user->isAdmin() || $user->isDeletionRequested()) {
            return;
        }

        $issued = $this->tokens->issue($user, TokenPurpose::ExternalClientInvitation, $client->organization, $actor);

        Mail::to($user->email)->queue(new ExternalClientInvitationMail($main, $client, $user, $issued->plainToken));
    }

    /**
     * The active client through which the user answers in the diagnosis.
     */
    public function clientFor(MainDiagnosis $main, User $user): ?ExternalClient
    {
        $organization = $main->diagnosis->organization;

        if (! $organization->usesExternalClients() || ! $organization->isActive()) {
            return null;
        }

        return $main->externalClients()
            ->active()
            ->whereHas('users', fn (Builder $users) => $users->whereKey($user->id))
            ->orderBy('name')
            ->first();
    }

    /**
     * The running diagnoses the user is asked in, the newest first (35 · 35.5).
     *
     * @return Collection<int, MainDiagnosis>
     */
    public function openMeasurementsFor(User $user): Collection
    {
        return MainDiagnosis::query()
            ->where('stage', MainDiagnosisStage::Running)
            ->whereHas('externalClients', fn (Builder $clients) => $clients
                ->whereNull('external_clients.deactivated_at')
                ->whereHas('users', fn (Builder $users) => $users->whereKey($user->id)))
            ->whereHas('diagnosis.organization', fn (Builder $organizations) => $organizations
                ->whereNull('deactivated_at')
                ->where('external_clients_enabled', true))
            ->with('diagnosis.organization')
            ->latest('id')
            ->get();
    }

    /**
     * The client's questions in this diagnosis, in the client's order: a value
     * only when it is in the diagnosis' frozen value set (18 · 18.1).
     *
     * @return Collection<int, ExternalClientItem>
     */
    public function itemsFor(MainDiagnosis $main, ExternalClient $client): Collection
    {
        $valueKeys = array_keys($this->valueNames($main));

        return $client->items()->with('process')->get()
            ->filter(fn (ExternalClientItem $item) => $item->kind === ExternalClientItemKind::Value
                ? in_array($item->value_key, $valueKeys, true)
                : $item->process !== null)
            ->values();
    }

    /**
     * The values of the diagnosis' frozen set: key => name.
     *
     * @return array<string, string>
     */
    public function valueNames(MainDiagnosis $main): array
    {
        return collect($main->valueSetVersion?->snapshot['values'] ?? [])->pluck('name', 'key')->all();
    }

    public function hasConsent(MainDiagnosis $main, User $user): bool
    {
        return Consent::query()
            ->where('main_diagnosis_id', $main->id)
            ->where('user_id', $user->id)
            ->where('kind', ConsentKind::Answers)
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * The client user's own act, with the exact text agreed to (25 · 25.2).
     */
    public function giveConsent(MainDiagnosis $main, User $user): void
    {
        if ($this->hasConsent($main, $user)) {
            return;
        }

        $organization = $main->diagnosis->organization;

        Consent::create([
            'user_id' => $user->id,
            'main_diagnosis_id' => $main->id,
            'kind' => ConsentKind::Answers,
            'text_version' => __('external_clients.consent.version'),
            'text' => __('external_clients.consent.text', ['organization' => $organization->name]),
            'given_at' => now(),
        ]);

        $this->audit->record(AuditEventType::ConsentGiven, $user, $user, ['diagnosis' => $main->diagnosis->name, 'kind' => ConsentKind::Answers->value], organization: $organization);
    }

    /**
     * The user's answers in the diagnosis by item id.
     *
     * @return Collection<int, ExternalClientAnswer>
     */
    public function answersFor(MainDiagnosis $main, User $user): Collection
    {
        return $main->externalClientAnswers()->where('user_id', $user->id)->get()->keyBy('external_client_item_id');
    }

    /**
     * A draft: the text as it stands, with the question it answers.
     */
    public function saveDraft(MainDiagnosis $main, ExternalClient $client, User $user, ExternalClientItem $item, string $text): ExternalClientAnswer
    {
        return ExternalClientAnswer::query()->updateOrCreate(
            ['main_diagnosis_id' => $main->id, 'user_id' => $user->id, 'external_client_item_id' => $item->id],
            [
                'external_client_id' => $client->id,
                'kind' => $item->kind,
                'value_key' => $item->value_key,
                'process_id' => $item->process_id,
                'question' => $item->question,
                'answer' => $text,
            ],
        );
    }

    /**
     * "Beküldöm": every answer with text becomes a material, a changed one
     * replaces its material, an emptied one takes it back. The number sent.
     */
    public function submit(MainDiagnosis $main, User $user): int
    {
        $sent = 0;
        $answers = $main->externalClientAnswers()->where('user_id', $user->id)->with('material')->get();

        foreach ($answers as $answer) {
            if (! $answer->hasAnswer()) {
                if ($answer->material !== null) {
                    $this->materials->delete($answer->material);
                }

                $answer->update(['material_id' => null, 'submitted_at' => null]);

                continue;
            }

            $sent++;
            $turns = $this->turns($answer);

            if ($answer->material?->text === MaterialStore::turnAttributes($turns)['text']) {
                if ($answer->submitted_at === null) {
                    $answer->update(['submitted_at' => now()]);
                }

                continue;
            }

            if ($answer->material !== null) {
                $this->materials->delete($answer->material);
            }

            $material = $this->materials->fromTurns($turns, MaterialContext::ExternalClient, $user, $main->diagnosis->organization);
            $answer->update(['material_id' => $material->id, 'submitted_at' => now()]);
            $this->pipeline->start($material);
        }

        return $sent;
    }

    /**
     * The question and the answer, as a written exchange.
     *
     * @return list<array{speaker: string, text: string}>
     */
    public function turns(ExternalClientAnswer $answer): array
    {
        return [
            ['speaker' => __('external_clients.speaker_question'), 'text' => $answer->question],
            ['speaker' => __('external_clients.speaker_client'), 'text' => (string) $answer->answer],
        ];
    }
}
