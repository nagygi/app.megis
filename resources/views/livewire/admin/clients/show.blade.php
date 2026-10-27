@php
    // The same status tones on every user list: clients, members, admins.
    $statusTones = ['active' => 'green', 'added' => 'neutral', 'invited' => 'yellow', 'pending' => 'yellow', 'invitation_expired' => 'coral', 'locked' => 'coral', 'inactive' => 'muted', 'archived' => 'muted'];
@endphp

<div class="flex max-w-5xl flex-col gap-6">
    <x-ui.flash />

    @if ($user->isAnonymized())
        {{-- A deleted user: only the log, without name and e-mail (33 · 33.7) --}}
        <x-admin.page-header :title="__('admin.clients.deleted_title', ['id' => $user->id, 'date' => $user->anonymized_at->format('Y.m.d.')])" :spaced="false" />
    @else
    <x-admin.page-header
        :title="$user->name"
        :description="$user->email.' · '.__('admin.clients.last_login').': '.($user->last_login_at?->format('Y.m.d. H:i') ?? __('admin.common.never'))"
        :spaced="false"
    >
        <x-slot:badges>
            <x-ui.badge :tone="$statusTones[$status]">{{ __('admin.clients.status_values.'.$status) }}</x-ui.badge>
            @if ($user->mail_undeliverable_at)
                <x-ui.badge tone="coral">{{ __('admin.common.mail_undeliverable') }}</x-ui.badge>
            @endif
        </x-slot:badges>
        <x-slot:actions>
            @if ($canRename)
                <x-ui.button tone="outline" size="sm" wire:click="startEditing">{{ __('admin.clients.edit_name') }}</x-ui.button>
            @endif
            @if ($canImpersonate)
                <form method="POST" action="{{ route('admin.impersonate', $user) }}" target="_blank">
                    @csrf
                    <x-ui.button type="submit" tone="coral" size="sm">{{ __('impersonation.start') }}</x-ui.button>
                </form>
            @endif
            <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $user->name])">
                @include('livewire.admin.clients.partials.account-actions', [
                    'client' => $user,
                    'withImpersonation' => false,
                    'statusReason' => $this->statusChangeUnavailableReason($user),
                    'archiveReason' => $this->archiveUnavailableReason($user),
                ])
            </x-admin.row-actions>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($editing)
        <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="last_name" wire:model="last_name" :label="__('admin.members.last_name')" />
                <x-form.input name="first_name" wire:model="first_name" :label="__('admin.members.first_name')" />
            </div>
            <x-form.input
                name="email"
                type="email"
                wire:model="email"
                :label="__('admin.common.email')"
                :help="__('admin.clients.email_change_help')"
                :disabled="! $canChangeEmail"
                required
            />
            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
                <x-ui.button tone="outline" wire:click="cancel">{{ __('admin.common.cancel') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card as="section" bordered>
        <h2 class="mb-4 text-lg font-semibold">{{ __('admin.clients.section_organizations') }}</h2>
        <ul class="flex flex-col divide-y divide-grey-200">
            @foreach ($memberships as $membership)
                @php($statusKey = $membership->status === \App\Enums\MembershipStatus::Invited && ! $membership->hasPendingInvitation() ? 'invitation_expired' : $membership->status->value)
                <li class="flex flex-wrap items-start justify-between gap-4 py-3" wire:key="client-membership-{{ $membership->id }}">
                    <div>
                        <a href="{{ route('admin.organizations.members', $membership->organization) }}" class="font-semibold text-charcoal">{{ $membership->organization->name }}</a>
                        <p class="text-sm">{{ implode(', ', $membership->tagLabels()) }}@if ($membership->position) · {{ $membership->position }}@endif</p>
                        @foreach ($teamsByOrganization->get($membership->organization_id, collect()) as $team)
                            <p class="text-sm text-grey-700">{{ $team->name }}@if ($team->pivot->is_leader) · {{ __('admin.members.team_leader') }}@endif</p>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge :tone="$statusTones[$statusKey]">{{ $membership->displayStatusLabel() }}</x-ui.badge>
                        @if ($membership->status === \App\Enums\MembershipStatus::Added)
                            <x-ui.button size="sm" icon="mail" wire:click="sendInvitation({{ $membership->id }})">{{ __('admin.members.send_invitation') }}</x-ui.button>
                        @elseif ($membership->status === \App\Enums\MembershipStatus::Invited)
                            <x-ui.button tone="outline" size="sm" icon="mail" wire:click="sendInvitation({{ $membership->id }})">{{ __('admin.members.resend_invitation') }}</x-ui.button>
                        @endif
                        @if ($membership->status === \App\Enums\MembershipStatus::Inactive)
                            <x-ui.button size="sm" tone="outline" wire:click="activateMembership({{ $membership->id }})" wire:confirm="{{ __('admin.members.confirm_activate') }}">{{ __('admin.common.activate') }}</x-ui.button>
                        @else
                            <x-ui.button size="sm" tone="danger" wire:click="deactivateMembership({{ $membership->id }})" wire:confirm="{{ __('admin.members.confirm_deactivate') }}" data-confirm-tone="danger">{{ __('admin.common.deactivate') }}</x-ui.button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    <x-ui.card as="section" bordered>
        <h2 class="mb-4 text-lg font-semibold">{{ __('admin.clients.section_acceptances') }}</h2>
        @forelse ($acceptances as $acceptance)
            <p class="py-1">
                {{ $acceptance->version->type->label() }} · {{ $acceptance->version->effective_from->format('Y.m.d.') }}
                <span class="text-grey-700">· {{ __('admin.clients.accepted_at') }}: {{ $acceptance->accepted_at->format('Y.m.d. H:i') }}</span>
            </p>
        @empty
            <p class="text-grey-700">{{ __('admin.clients.no_acceptances') }}</p>
        @endforelse
    </x-ui.card>

    {{-- Diagnózisok (33 · 33.3) --}}
    <x-ui.card as="section" bordered>
        <h2 class="mb-4 text-lg font-semibold">{{ __('admin.clients.section_diagnoses') }}</h2>
        @forelse ($mains as $main)
            <div class="border-b border-grey-200 py-2 last:border-0">
                <p>
                    <a href="{{ route('admin.main-diagnoses.open', $main->diagnosis) }}" class="font-semibold">{{ $main->diagnosis->name }}</a>
                    <span class="text-grey-700">· {{ $main->participants->map(fn ($participant) => __('enums.participant_role.'.$participant->role->value))->unique()->implode(', ') }} · {{ $main->stage->label() }}</span>
                </p>
                @foreach ($subdiagnoses->get($main->diagnosis_id, collect()) as $subdiagnosis)
                    <p class="pl-4 text-sm"><a href="{{ route('admin.diagnoses.show', $subdiagnosis) }}">{{ $subdiagnosis->type->name }} · {{ $subdiagnosis->diagnosed_on->format('Y.m.d.') }}</a></p>
                @endforeach
            </div>
        @empty
            <p class="text-grey-700">{{ __('admin.clients.no_diagnoses') }}</p>
        @endforelse
    </x-ui.card>

    {{-- Hozzájárulások (33 · 33.5), read only --}}
    @if ($consents->isNotEmpty())
        <x-ui.card as="section" bordered>
            <h2 class="mb-4 text-lg font-semibold">{{ __('admin.clients.section_consents') }}</h2>
            @foreach ($consents as $consent)
                <div class="border-b border-grey-200 py-2 last:border-0">
                    <p>{{ __('enums.consent_kind.'.$consent->kind->value) }} · {{ $consent->mainDiagnosis?->diagnosis?->name }}</p>
                    <p class="text-sm text-grey-700">„{{ $consent->text }}” · {{ $consent->text_version }}</p>
                    <p class="text-sm text-grey-700">{{ __('admin.clients.consent_given', ['date' => $consent->given_at->format('Y.m.d. H:i')]) }}@if ($consent->revoked_at) · {{ __('admin.clients.consent_revoked', ['date' => $consent->revoked_at->format('Y.m.d. H:i')]) }}@endif</p>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    @if ($mirrors->isNotEmpty())
        {{-- Értéktükrök with the ticked curiosity questions (06 · 6.1, ET5) --}}
        <x-ui.card as="section" bordered>
            <h2 class="mb-4 text-lg font-semibold">{{ __('admin.clients.section_mirrors') }}</h2>
            @foreach ($mirrors as $mirror)
                <div class="border-b border-grey-200 py-2 last:border-0">
                    <p>
                        <span class="font-semibold">{{ $mirror->created_at->format('Y.m.d.') }}</span>
                        @if ($mirror->diagnosis)
                            · <a href="{{ route('admin.diagnoses.show', $mirror->diagnosis) }}" class="text-charcoal">{{ $mirror->diagnosis->name }}</a>
                        @endif
                        @if ($mirror->booking_requested_at)
                            <x-ui.badge tone="yellow" class="ml-1">{{ __('admin.clients.mirror_booking', ['date' => $mirror->booking_requested_at->format('Y.m.d. H:i')]) }}</x-ui.badge>
                        @endif
                    </p>
                    <p class="text-sm text-grey-700">
                        {{ __('admin.clients.mirror_curiosity') }}:
                        {{ collect($mirror->curiosity ?? [])->map(fn ($key) => __('mirror.curiosity.'.$key.'.question'))->implode(' · ') ?: __('admin.common.none') }}
                    </p>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    @endif

    <x-ui.card as="section" bordered>
        <h2 class="mb-4 text-lg font-semibold">{{ __('admin.clients.section_log') }}</h2>
        @forelse ($events as $event)
            <div class="border-b border-grey-200 py-2 last:border-0" wire:key="client-log-{{ $event->id }}">
                <p>
                    <span class="font-semibold">{{ $event->type->label() }}</span>
                    <span class="text-grey-700">· {{ $event->created_at->format('Y.m.d. H:i') }}@if ($event->actor) · {{ $event->actor->name }}@endif @if ($event->organization) · {{ $event->organization->name }}@endif</span>
                </p>
                @if ($event->type === \App\Enums\AuditEventType::EmailChanged && $event->payload)
                    <p class="flex flex-wrap items-center gap-1 text-sm text-grey-700">{{ $event->payload['old'] ?? '' }} <x-megis.icon name="arrow-right" :label="__('admin.clients.changed_to')" /> {{ $event->payload['new'] ?? '' }}</p>
                @elseif ($detail = $event->detail())
                    <p class="text-sm text-grey-700">{{ $detail }}</p>
                @endif
            </div>
        @empty
            <p class="text-grey-700">{{ __('admin.clients.no_log') }}</p>
        @endforelse

        @if ($events->hasPages())
            <div class="mt-4">{{ $events->links() }}</div>
        @endif
    </x-ui.card>
</div>
