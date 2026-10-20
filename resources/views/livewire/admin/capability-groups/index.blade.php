@php
    [$sortField, $sortDirection] = $this->currentSort();
    // An empty list says "Nincs találat." only when the search hid the rows.
    $filtering = filled($search);
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.capability_groups.title')">
        <x-slot:actions>
            <x-ui.button icon="plus" :href="route('admin.capability-groups.create')">{{ __('admin.capability_groups.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar />

    <x-admin.table :paginator="$groups">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capability_groups.name') }}</x-admin.th>
            <x-admin.th field="name_en" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capability_groups.name_en') }}</x-admin.th>
            <x-admin.th>{{ __('admin.capability_groups.description') }}</x-admin.th>
            <x-admin.th field="capabilities" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capability_groups.capabilities') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($groups as $group)
            <tr wire:key="group-{{ $group->id }}">
                <td class="px-3 py-2"><a href="{{ route('admin.capability-groups.edit', $group) }}" class="font-semibold text-charcoal">{{ $group->name }}</a></td>
                <td class="px-3 py-2 text-grey-700">{{ $group->name_en ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2 text-grey-700">{{ $group->description ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">
                    <a href="{{ route('admin.capabilities.index', ['szuro' => ['group' => $group->id]]) }}" class="text-charcoal">{{ $group->capabilities_count }}</a>
                </td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $group->name])">
                        <x-admin.row-action :href="route('admin.capability-groups.edit', $group)">{{ __('admin.common.edit') }}</x-admin.row-action>
                        @if ($group->capabilities_count === 0)
                            <x-admin.row-action
                                tone="danger"
                                wire:click="delete({{ $group->id }})"
                                wire:confirm="{{ __('admin.capability_groups.confirm_delete') }}"
                                data-confirm-tone="danger"
                                :data-confirm-label="__('admin.common.delete')"
                            >{{ __('admin.common.delete') }}</x-admin.row-action>
                        @endif
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="5">
                {{ $filtering ? __('admin.common.empty') : __('admin.capability_groups.empty') }}
                @unless ($filtering)
                    <x-slot:action>
                        <x-ui.button tone="outline" icon="plus" :href="route('admin.capability-groups.create')">{{ __('admin.capability_groups.create') }}</x-ui.button>
                    </x-slot:action>
                @endunless
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
