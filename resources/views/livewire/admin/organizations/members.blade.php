@php
    [$sortField, $sortDirection] = $this->currentSort();
    // The same status tones on every user list: clients, members, admins.
    $statusTones = ['active' => 'green', 'added' => 'neutral', 'invited' => 'yellow', 'pending' => 'yellow', 'invitation_expired' => 'coral', 'locked' => 'coral', 'inactive' => 'muted'];
    // An empty list says "Nincs találat." only when a search or a filter hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.members.headcount', ['count' => $headcountLabel])">
        <x-slot:actions>
            @if ($hasFreeSeat)
                <x-ui.button icon="plus" :href="route('admin.organizations.members.create', $organization)">{{ __('admin.members.invite') }}</x-ui.button>
            @else
                <div class="flex flex-col items-end gap-1">
                    <x-ui.button icon="plus" disabled aria-describedby="limit-reason">{{ __('admin.members.invite') }}</x-ui.button>
                    <p id="limit-reason" class="text-sm text-grey-700">{{ $limitMessage }}</p>
                </div>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar>
        <x-admin.filter name="status" :label="__('admin.members.status')" :options="$statusOptions" />
        <x-admin.filter name="tag" :label="__('admin.members.tags')" :options="$tagOptions" />
        @if ($teamOptions->isNotEmpty())
            <x-admin.filter name="team" :label="__('admin.members.teams')" :options="$teamOptions" />
        @endif
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$memberships">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.members.name') }}</x-admin.th>
            <x-admin.th field="email" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.members.email') }}</x-admin.th>
            <x-admin.th field="position" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.members.position') }}</x-admin.th>
            <x-admin.th>{{ __('admin.members.tags') }}</x-admin.th>
            <x-admin.th>{{ __('admin.members.teams') }}</x-admin.th>
            <x-admin.th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.members.status') }}</x-admin.th>
            <x-admin.th field="last_login" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.members.last_login') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($memberships as $membership)
            @php
                $member = $membership->user;
                $statusKey = $membership->status === \App\Enums\MembershipStatus::Invited && ! $membership->hasPendingInvitation() ? 'invitation_expired' : $membership->status->value;
            @endphp
            <tr wire:key="membership-{{ $membership->id }}">
                <td class="px-3 py-2">
                    <a href="{{ route('admin.clients.show', $member) }}" class="font-semibold text-charcoal">{{ $member->name }}</a>
                    @if ($member->mail_undeliverable_at)
                        <x-ui.badge tone="coral" class="mt-1">{{ __('admin.common.mail_undeliverable') }}</x-ui.badge>
                    @endif
                </td>
                <td class="px-3 py-2">{{ $member->isAnonymized() ? __('admin.common.none') : $member->email }}</td>
                <td class="px-3 py-2">{{ $membership->position ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">{{ implode(', ', $membership->tagLabels()) }}</td>
                <td class="px-3 py-2">
                    @foreach ($member->teams as $team)
                        <span class="block whitespace-nowrap">{{ $team->name }}@if ($team->pivot->is_leader) <span class="text-grey-700">· {{ __('admin.members.team_leader') }}</span>@endif</span>
                    @endforeach
                </td>
                <td class="px-3 py-2">
                    <x-ui.badge :tone="$statusTones[$statusKey]">{{ $membership->displayStatusLabel() }}</x-ui.badge>
                </td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $member->last_login_at?->format('Y.m.d. H:i') ?? __('admin.common.never') }}</td>
                <td class="px-3 py-2">
                    <div class="flex items-center justify-end gap-2">
                        {{-- A recorded user's main action is the first invitation: one button, not repeated in the menu. --}}
                        @if ($membership->status === \App\Enums\MembershipStatus::Added)
                            <x-ui.button size="sm" icon="mail" wire:click="sendInvitation({{ $membership->id }})">{{ __('admin.members.send_invitation') }}</x-ui.button>
                        @endif
                        <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $member->name])">
                            <x-admin.row-action :href="route('admin.organizations.members.edit', [$organization, $membership])">{{ __('admin.common.edit') }}</x-admin.row-action>
                            @if ($membership->status === \App\Enums\MembershipStatus::Invited)
                                <x-admin.row-action wire:click="sendInvitation({{ $membership->id }})">{{ __('admin.members.resend_invitation') }}</x-admin.row-action>
                            @endif
                            @if ($membership->status === \App\Enums\MembershipStatus::Inactive)
                                <x-admin.row-action wire:click="activate({{ $membership->id }})" wire:confirm="{{ __('admin.members.confirm_activate') }}">{{ __('admin.common.activate') }}</x-admin.row-action>
                            @else
                                <x-admin.row-action tone="danger" wire:click="deactivate({{ $membership->id }})" wire:confirm="{{ __('admin.members.confirm_deactivate') }}" data-confirm-tone="danger">{{ __('admin.common.deactivate') }}</x-admin.row-action>
                            @endif
                            {{-- Admin accounts get no such item at all (14 · 14.6). --}}
                            @unless ($member->isAdmin())
                                @php($impersonationUnavailableReason = $this->impersonationUnavailableReason($membership))
                                @if ($impersonationUnavailableReason)
                                    <x-admin.row-action :disabled-reason="$impersonationUnavailableReason">{{ __('admin.members.impersonate') }}</x-admin.row-action>
                                @else
                                    <form method="POST" action="{{ route('admin.impersonate', $member) }}" target="_blank">
                                        @csrf
                                        <x-admin.row-action type="submit">{{ __('admin.members.impersonate') }}</x-admin.row-action>
                                    </form>
                                @endif
                            @endunless
                        </x-admin.row-actions>
                    </div>
                </td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="8">
                {{ $filtering ? __('admin.common.empty') : __('admin.members.empty') }}
                @if (! $filtering && $hasFreeSeat)
                    <x-slot:action>
                        <x-ui.button tone="outline" icon="plus" :href="route('admin.organizations.members.create', $organization)">{{ __('admin.members.invite') }}</x-ui.button>
                    </x-slot:action>
                @endif
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
