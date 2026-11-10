@php
    [$sortField, $sortDirection] = $this->currentSort();
    // An empty list says "Nincs találat." only when the search hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.organizations.tabs.external-clients')" :description="__('admin.external_clients.intro')">
        <x-slot:actions>
            <x-ui.button icon="plus" :href="route('admin.organizations.external-clients.create', $organization)">{{ __('admin.external_clients.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar />

    <x-admin.table :paginator="$clients">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.external_clients.name') }}</x-admin.th>
            <x-admin.th field="users" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.external_clients.users') }}</x-admin.th>
            <x-admin.th>{{ __('admin.external_clients.measured') }}</x-admin.th>
            <x-admin.th>{{ __('admin.external_clients.status') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($clients as $client)
            <tr wire:key="external-client-{{ $client->id }}">
                <td class="px-3 py-2">
                    <x-ui.button tone="ghost" size="sm" class="-ml-1.5" :href="route('admin.organizations.external-clients.edit', [$organization, $client])">{{ $client->name }}</x-ui.button>
                    @if ($client->description)
                        <p class="text-sm text-grey-700">{{ $client->description }}</p>
                    @endif
                </td>
                <td class="px-3 py-2">{{ $client->users_count }}</td>
                <td class="px-3 py-2">{{ __('admin.external_clients.measured_counts', ['values' => $client->values_count, 'processes' => $client->processes_count]) }}</td>
                <td class="px-3 py-2">
                    <x-admin.user-status :status="$client->isActive() ? 'active' : 'inactive'">{{ $client->isActive() ? __('admin.organizations.active') : __('admin.organizations.inactive') }}</x-admin.user-status>
                </td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $client->name])">
                        <x-admin.row-action :href="route('admin.organizations.external-clients.edit', [$organization, $client])">{{ __('admin.common.edit') }}</x-admin.row-action>
                        @if ($client->isActive())
                            <x-admin.row-action wire:click="deactivate({{ $client->id }})" wire:confirm="{{ __('admin.external_clients.confirm_deactivate') }}">{{ __('admin.common.deactivate') }}</x-admin.row-action>
                        @else
                            <x-admin.row-action wire:click="activate({{ $client->id }})">{{ __('admin.common.activate') }}</x-admin.row-action>
                        @endif
                        <x-admin.row-action tone="danger" wire:click="delete({{ $client->id }})" wire:confirm="{{ __('admin.external_clients.confirm_delete') }}" data-confirm-tone="danger">{{ __('admin.common.delete') }}</x-admin.row-action>
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="5">
                {{ $filtering ? __('admin.common.empty') : __('admin.external_clients.empty') }}
                @unless ($filtering)
                    <x-slot:action>
                        <x-ui.button tone="outline" icon="plus" :href="route('admin.organizations.external-clients.create', $organization)">{{ __('admin.external_clients.create') }}</x-ui.button>
                    </x-slot:action>
                @endunless
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
