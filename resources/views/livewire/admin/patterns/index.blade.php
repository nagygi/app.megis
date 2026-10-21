@php
    [$sortField, $sortDirection] = $this->currentSort();
    // An empty list says "Nincs találat." only when a search or filter hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-admin.page-header :title="__('admin.patterns.title')">
        <x-slot:actions>
            <x-ui.button tone="outline" :href="route('admin.capabilities.import')">{{ __('admin.capabilities.import.title') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar :search-label="__('admin.patterns.search_label')">
        <x-admin.filter name="capability" :label="__('admin.patterns.filters.capability')" :options="$capabilities" />
        <x-admin.filter name="type" :label="__('admin.patterns.filters.type')" :options="$types" />
        <x-admin.filter name="subtype" :label="__('admin.patterns.filters.subtype')" :options="$subtypes" />
        <x-admin.filter name="maturity" :label="__('admin.patterns.filters.maturity')" :options="$maturities" />
        <x-admin.filter name="level" :label="__('admin.patterns.filters.level')" :options="$levels" />
        <x-admin.filter name="product" :label="__('admin.patterns.filters.product')" :options="$products" />
        <x-admin.filter name="validity" :label="__('admin.patterns.filters.validity')" :all-label="__('admin.patterns.valid_only')" :options="['obsolete' => __('enums.pattern_validity.obsolete'), 'rejected' => __('enums.pattern_validity.rejected'), 'all' => __('admin.common.all')]" />
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$patterns">
        <x-slot:head>
            <x-admin.th field="code" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.patterns.code') }}</x-admin.th>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.patterns.name') }}</x-admin.th>
            <x-admin.th field="type" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.patterns.type') }}</x-admin.th>
            <x-admin.th>{{ __('admin.patterns.capabilities') }}</x-admin.th>
            <x-admin.th>{{ __('admin.patterns.maturity') }}</x-admin.th>
            <x-admin.th field="counts" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.patterns.counts') }}</x-admin.th>
            <x-admin.th field="version" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.patterns.version') }}</x-admin.th>
        </x-slot:head>

        @forelse ($patterns as $pattern)
            <tr wire:key="pattern-{{ $pattern->id }}">
                <td class="px-3 py-2 font-mono text-sm whitespace-nowrap">{{ $pattern->code }}</td>
                <td class="px-3 py-2">
                    <a href="{{ route('admin.patterns.show', $pattern) }}" class="font-semibold text-charcoal">{{ $pattern->name }}</a>
                </td>
                <td class="px-3 py-2">
                    <span class="flex flex-wrap gap-1">
                        <x-ui.badge :tone="$pattern->type === \App\Enums\PatternType::DetectorError ? 'coral' : 'neutral'">{{ $pattern->type->label() }}</x-ui.badge>
                        @if ($pattern->subtype)
                            <x-ui.badge tone="muted">{{ $pattern->subtype->label() }}</x-ui.badge>
                        @endif
                    </span>
                </td>
                <td class="px-3 py-2 font-mono text-sm">
                    {{ $pattern->capabilities->pluck('code')->implode(', ') ?: __('admin.common.none') }}
                </td>
                <td class="px-3 py-2">{{ $pattern->maturity()->label() }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-grey-700">{{ __('admin.patterns.counts_value', ['clients' => $pattern->sheet_approved_clients, 'evidence' => $pattern->sheet_evidence_count]) }}</td>
                <td class="px-3 py-2">{{ $pattern->latestVersion?->label() ?? __('admin.common.none') }}</td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="7">
                {{ $filtering ? __('admin.common.empty') : __('admin.patterns.empty') }}
                @unless ($filtering)
                    <x-slot:action>
                        <x-ui.button tone="outline" :href="route('admin.capabilities.import')">{{ __('admin.capabilities.import.title') }}</x-ui.button>
                    </x-slot:action>
                @endunless
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
