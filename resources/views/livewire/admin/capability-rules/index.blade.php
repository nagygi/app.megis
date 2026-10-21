@php
    [$sortField, $sortDirection] = $this->currentSort();
    // An empty list says "Nincs találat." only when a search or filter hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-admin.page-header :title="__('admin.capability_rules.title')" />

    <p class="mb-6 max-w-3xl text-grey-700">{{ __('admin.capability_rules.lead') }}</p>

    <x-admin.list-toolbar :search-label="__('admin.capability_rules.search_label')">
        <x-admin.filter name="area" :label="__('admin.capability_rules.area')" :options="$areas" />
        <x-admin.filter name="status" :label="__('admin.capability_rules.status')" :options="$statuses" />
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$rules">
        <x-slot:head>
            <x-admin.th field="code" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capability_rules.code') }}</x-admin.th>
            <x-admin.th field="area" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capability_rules.area') }}</x-admin.th>
            <x-admin.th>{{ __('admin.capability_rules.text') }}</x-admin.th>
            <x-admin.th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.capability_rules.status') }}</x-admin.th>
            <x-admin.th>{{ __('admin.capability_rules.source') }}</x-admin.th>
        </x-slot:head>

        @forelse ($rules as $rule)
            <tr wire:key="rule-{{ $rule->id }}">
                <td class="px-3 py-2 align-top font-mono text-sm whitespace-nowrap">{{ $rule->code }}</td>
                <td class="px-3 py-2 align-top">{{ $rule->area?->label() ?? __('admin.capability_rules.other_area') }}</td>
                <td class="px-3 py-2 align-top whitespace-pre-line">{{ $rule->text }}</td>
                <td class="px-3 py-2 align-top text-grey-700">{{ $rule->status_label ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2 align-top text-sm text-grey-700">
                    {{ collect([$rule->source, $rule->version, $rule->decided_on])->filter()->implode(' · ') ?: __('admin.common.none') }}
                </td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="5">
                {{ $filtering ? __('admin.common.empty') : __('admin.capability_rules.empty') }}
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
