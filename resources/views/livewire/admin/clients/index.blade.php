@php
    [$sortField, $sortDirection] = $this->currentSort();
    // The same status tones on every user list: clients, members, admins.
    $statusTones = ['active' => 'green', 'added' => 'neutral', 'invited' => 'yellow', 'pending' => 'yellow', 'invitation_expired' => 'coral', 'locked' => 'coral', 'inactive' => 'muted', 'archived' => 'muted'];
    // An empty list says "Nincs találat." only when a search or a filter hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.clients.title')" />

    <x-admin.list-toolbar>
        <x-admin.filter name="organization" :label="__('admin.clients.organizations')" :options="$organizationOptions" />
        <x-admin.filter name="status" :label="__('admin.clients.status')" :options="$statusOptions" />
        <x-admin.filter name="mirror" :label="__('admin.clients.mirror_filter')" :options="$mirrorOptions" />
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$clients">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.common.name') }}</x-admin.th>
            <x-admin.th field="email" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.common.email') }}</x-admin.th>
            <x-admin.th>{{ __('admin.clients.organizations') }}</x-admin.th>
            <x-admin.th>{{ __('admin.clients.status') }}</x-admin.th>
            <x-admin.th field="last_login" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.clients.last_login') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($clients as $client)
            @php($status = $client->clientStatus())
            <tr wire:key="client-{{ $client->id }}">
                <td class="px-3 py-2">
                    <a href="{{ route('admin.clients.show', $client) }}" class="font-semibold text-charcoal">{{ $client->name }}</a>
                    @if ($client->mail_undeliverable_at)
                        <x-ui.badge tone="coral" class="mt-1">{{ __('admin.common.mail_undeliverable') }}</x-ui.badge>
                    @endif
                </td>
                {{-- A deleted user's technical address is not shown (33 · 33.7). --}}
                <td class="px-3 py-2">{{ $client->isAnonymized() ? __('admin.common.none') : $client->email }}</td>
                <td class="px-3 py-2">{{ $client->memberships->pluck('organization.name')->join(', ') ?: __('admin.common.none') }}</td>
                <td class="px-3 py-2"><x-ui.badge :tone="$statusTones[$status]">{{ __('admin.clients.status_values.'.$status) }}</x-ui.badge></td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $client->last_login_at?->format('Y.m.d. H:i') ?? __('admin.common.never') }}</td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $client->name])">
                        @include('livewire.admin.clients.partials.account-actions', [
                            'client' => $client,
                            'impersonationReason' => $this->impersonationUnavailableReason($client),
                            'statusReason' => $this->statusChangeUnavailableReason($client),
                            'archiveReason' => $this->archiveUnavailableReason($client),
                        ])
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            {{-- New users are added through an organization, so there is no "add" here. --}}
            <x-admin.empty-row :colspan="6">{{ $filtering ? __('admin.common.empty') : __('admin.clients.empty') }}</x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
