@php
    [$sortField, $sortDirection] = $this->currentSort();
    // An empty list says "Nincs találat." only when a search or filter hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.values.title')">
        <x-slot:actions>
            <x-ui.button tone="outline" :href="route('admin.values.import')">{{ __('admin.values.import.title') }}</x-ui.button>
            <x-ui.button icon="plus" :href="route('admin.values.create')">{{ __('admin.values.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar :search-label="__('admin.values.search_label')">
        <x-admin.filter name="higher_order" :label="__('admin.values.higher_order')" :options="$higherOrders" />
        <x-admin.filter name="category" :label="__('admin.values.category')" :options="$categories" />
        <x-admin.filter name="evidence" :label="__('admin.values.evidence')" :options="$grades" />
        <x-admin.filter name="svs" :label="__('admin.values.svs')" :options="$svsStatuses" />
        <x-admin.filter name="status" :label="__('admin.values.status')" :all-label="__('admin.values.active')" :options="['archived' => __('admin.values.archived'), 'all' => __('admin.common.all')]" />
        <x-admin.filter name="origin" :label="__('admin.values.origin')" :options="$origins" />
        <x-admin.filter name="review" :label="__('admin.values.review')" :options="['awaiting' => __('admin.values.awaiting_review'), 'reviewed' => __('admin.values.reviewed')]" />
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$values">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.values.name') }}</x-admin.th>
            <x-admin.th field="category" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.values.category') }}</x-admin.th>
            <x-admin.th field="version" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.values.version') }}</x-admin.th>
            <x-admin.th field="updated" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.values.updated') }}</x-admin.th>
            <x-admin.th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.values.status') }}</x-admin.th>
            <x-admin.th field="origin" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.values.origin') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($values as $value)
            <tr wire:key="value-{{ $value->id }}">
                <td class="px-3 py-2"><a href="{{ route('admin.values.edit', $value) }}" class="font-semibold text-charcoal">{{ $value->name }}</a></td>
                <td class="px-3 py-2 text-grey-700">{{ $value->category?->name ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">{{ $value->latestVersion?->label() ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-grey-700">
                    @if ($value->latestVersion)
                        {{ $value->latestVersion->created_at->format('Y.m.d. H:i') }}
                        <span class="block text-sm">{{ $value->latestVersion->creator?->name ?? __('admin.versions.system') }}</span>
                    @else
                        {{ __('admin.common.none') }}
                    @endif
                </td>
                <td class="px-3 py-2">
                    @if ($value->isArchived())
                        <x-ui.badge tone="muted">{{ __('admin.values.archived') }}</x-ui.badge>
                    @else
                        <x-ui.badge tone="green">{{ __('admin.values.active') }}</x-ui.badge>
                    @endif
                </td>
                <td class="px-3 py-2">
                    <div class="flex flex-wrap gap-1">
                        @if ($value->origin->needsReview())
                            <x-ui.badge>{{ $value->origin->label() }}</x-ui.badge>
                        @endif
                        @unless ($value->isReviewed())
                            <x-ui.badge tone="yellow">{{ __('admin.values.awaiting_review') }}</x-ui.badge>
                        @endunless
                    </div>
                </td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $value->name])">
                        <x-admin.row-action :href="route('admin.values.edit', $value)">{{ __('admin.common.edit') }}</x-admin.row-action>
                        <x-admin.row-action :href="route('admin.values.versions', $value)">{{ __('admin.values.tabs.versions') }}</x-admin.row-action>
                        @unless ($value->isReviewed())
                            <x-admin.row-action wire:click="markReviewed({{ $value->id }})">{{ __('admin.values.mark_reviewed') }}</x-admin.row-action>
                        @endunless
                        @if ($value->isArchived())
                            <x-admin.row-action wire:click="restore({{ $value->id }})">{{ __('admin.values.restore') }}</x-admin.row-action>
                        @else
                            <x-admin.row-action
                                tone="danger"
                                wire:click="archive({{ $value->id }})"
                                wire:confirm="{{ __('admin.values.confirm_archive') }}"
                                data-confirm-tone="danger"
                                :data-confirm-label="__('admin.values.archive')"
                            >{{ __('admin.values.archive') }}</x-admin.row-action>
                        @endif
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="7">
                {{ $filtering ? __('admin.common.empty') : __('admin.values.empty') }}
                @unless ($filtering)
                    <x-slot:action>
                        <x-ui.button tone="outline" icon="plus" :href="route('admin.values.create')">{{ __('admin.values.create') }}</x-ui.button>
                    </x-slot:action>
                @endunless
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
