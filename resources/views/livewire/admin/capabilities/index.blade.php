@php([$sortField, $sortDirection] = $this->currentSort())

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.capabilities.title')">
        <x-slot:actions>
            <x-ui.button tone="outline" :href="route('admin.capabilities.import')">{{ __('admin.capabilities.import.title') }}</x-ui.button>
            <x-ui.button :href="route('admin.capabilities.create')">{{ __('admin.capabilities.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar :search-label="__('admin.capabilities.search_label')">
        <x-admin.filter name="group" :label="__('admin.capabilities.group')" :options="$groups" />
        <x-admin.filter name="level" :label="__('admin.capabilities.level')" :options="$levels" />
        <x-admin.filter name="status" :label="__('admin.capabilities.status')" :options="$statuses" />
        <x-admin.filter name="nature" :label="__('admin.capabilities.nature')" :options="$natures" />
        <x-admin.filter name="state" :label="__('admin.capabilities.state')" :all-label="__('admin.capabilities.active')" :options="['archived' => __('admin.capabilities.archived'), 'all' => __('admin.common.all')]" />
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$capabilities">
        <x-slot:head>
            <x-admin.th field="code" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capabilities.code') }}</x-admin.th>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capabilities.name') }}</x-admin.th>
            <x-admin.th field="group" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capabilities.group') }}</x-admin.th>
            <x-admin.th field="level" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capabilities.level') }}</x-admin.th>
            <x-admin.th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capabilities.status') }}</x-admin.th>
            <x-admin.th field="version" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capabilities.version') }}</x-admin.th>
            <x-admin.th field="updated" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capabilities.updated') }}</x-admin.th>
            <x-admin.th field="state" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capabilities.state') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($capabilities as $capability)
            <tr wire:key="capability-{{ $capability->id }}">
                <td class="px-3 py-2 font-mono text-sm whitespace-nowrap">{{ $capability->code }}</td>
                <td class="px-3 py-2">
                    <a href="{{ route('admin.capabilities.edit', $capability) }}" class="font-semibold text-charcoal">{{ $capability->name }}</a>
                    @if ($capability->name_en)
                        <span class="block text-sm text-grey-600">{{ $capability->name_en }}</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-grey-700">{{ $capability->group->name }}</td>
                <td class="px-3 py-2">{{ $capability->level->label() }}</td>
                <td class="px-3 py-2">{{ $capability->status->label() }}</td>
                <td class="px-3 py-2">{{ $capability->latestVersion?->label() ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-grey-700">
                    @if ($capability->latestVersion)
                        {{ $capability->latestVersion->created_at->format('Y.m.d. H:i') }}
                        <span class="block text-sm">{{ $capability->latestVersion->creator?->name ?? __('admin.versions.system') }}</span>
                    @else
                        {{ __('admin.common.none') }}
                    @endif
                </td>
                <td class="px-3 py-2">
                    @if ($capability->isArchived())
                        <x-ui.badge tone="muted">{{ __('admin.capabilities.archived') }}</x-ui.badge>
                    @else
                        <x-ui.badge tone="green">{{ __('admin.capabilities.active') }}</x-ui.badge>
                    @endif
                </td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $capability->name])">
                        <x-admin.row-action :href="route('admin.capabilities.edit', $capability)">{{ __('admin.common.edit') }}</x-admin.row-action>
                        <x-admin.row-action :href="route('admin.capabilities.versions', $capability)">{{ __('admin.capabilities.tabs.versions') }}</x-admin.row-action>
                        @if ($capability->isArchived())
                            <x-admin.row-action wire:click="restore({{ $capability->id }})">{{ __('admin.capabilities.restore') }}</x-admin.row-action>
                        @else
                            <x-admin.row-action wire:click="archive({{ $capability->id }})" wire:confirm="{{ __('admin.capabilities.confirm_archive') }}">{{ __('admin.capabilities.archive') }}</x-admin.row-action>
                        @endif
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="px-3 py-6 text-center text-grey-600">{{ __('admin.common.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
</div>
