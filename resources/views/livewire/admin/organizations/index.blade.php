@php
    [$sortField, $sortDirection] = $this->currentSort();
    // An empty list says "Nincs találat." only when a search or a filter hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.organizations.title')">
        <x-slot:actions>
            <x-ui.button icon="plus" :href="route('admin.organizations.create')">{{ __('admin.organizations.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar>
        <x-admin.filter name="type" :label="__('admin.organizations.type')" :options="$typeOptions" />
        <x-admin.filter name="product" :label="__('admin.organizations.product')" :options="$productOptions" />
        <x-admin.filter name="contact" :label="__('admin.organizations.contact')" :options="$contactOptions" />
        <x-admin.filter name="status" :label="__('admin.organizations.status')" :options="$statusOptions" />
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$organizations">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.organizations.name') }}</x-admin.th>
            <x-admin.th field="type" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.organizations.type') }}</x-admin.th>
            <x-admin.th field="product" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.organizations.product') }}</x-admin.th>
            <x-admin.th field="users" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.organizations.users') }}</x-admin.th>
            <x-admin.th field="contact" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.organizations.contact') }}</x-admin.th>
            <x-admin.th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.organizations.status') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($organizations as $organization)
            <tr wire:key="organization-{{ $organization->id }}">
                <td class="px-3 py-2">
                    <a href="{{ route('admin.organizations.edit', $organization) }}" class="font-semibold text-charcoal">{{ $organization->name }}</a>
                    <span class="block text-xs text-grey-700">/{{ $organization->slug }}</span>
                </td>
                <td class="px-3 py-2">{{ $organization->type->label() }}</td>
                <td class="px-3 py-2">{{ $organization->product->name }}</td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $organization->headcountLabel() }}</td>
                <td class="px-3 py-2">{{ $organization->contactAdmin?->name ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">
                    <x-ui.badge :tone="$organization->isActive() ? 'green' : 'muted'">{{ $organization->isActive() ? __('admin.organizations.active') : __('admin.organizations.inactive') }}</x-ui.badge>
                </td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $organization->name])">
                        <x-admin.row-action :href="route('admin.organizations.edit', $organization)">{{ __('admin.common.edit') }}</x-admin.row-action>
                        @if ($organization->isActive())
                            <x-admin.row-action tone="danger" wire:click="deactivate({{ $organization->id }})" wire:confirm="{{ __('admin.organizations.confirm_deactivate') }}" data-confirm-tone="danger">{{ __('admin.common.deactivate') }}</x-admin.row-action>
                        @else
                            <x-admin.row-action wire:click="activate({{ $organization->id }})" wire:confirm="{{ __('admin.organizations.confirm_activate') }}">{{ __('admin.common.activate') }}</x-admin.row-action>
                        @endif
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="7">
                {{ $filtering ? __('admin.common.empty') : __('admin.organizations.empty') }}
                @unless ($filtering)
                    <x-slot:action>
                        <x-ui.button tone="outline" icon="plus" :href="route('admin.organizations.create')">{{ __('admin.organizations.create') }}</x-ui.button>
                    </x-slot:action>
                @endunless
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
