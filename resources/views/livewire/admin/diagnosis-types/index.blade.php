@php([$sortField, $sortDirection] = $this->currentSort())

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.diagnosis_types.title')">
        <x-slot:actions>
            <x-ui.button :href="route('admin.diagnosis-types.create')">{{ __('admin.diagnosis_types.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar />

    <x-admin.table :paginator="$types">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.diagnosis_types.name') }}</x-admin.th>
            <x-admin.th>{{ __('admin.diagnosis_types.description') }}</x-admin.th>
            <x-admin.th field="diagnoses" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.diagnosis_types.diagnoses') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($types as $type)
            <tr wire:key="type-{{ $type->id }}">
                <td class="px-3 py-2"><a href="{{ route('admin.diagnosis-types.edit', $type) }}" class="font-semibold text-charcoal">{{ $type->name }}</a></td>
                <td class="px-3 py-2 text-grey-700">{{ $type->description ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">{{ $type->diagnoses_count }}</td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $type->name])">
                        <x-admin.row-action :href="route('admin.diagnosis-types.edit', $type)">{{ __('admin.common.edit') }}</x-admin.row-action>
                        @if (! $type->isSystemType() && $type->diagnoses_count === 0)
                            <x-admin.row-action wire:click="delete({{ $type->id }})" wire:confirm="{{ __('admin.diagnosis_types.confirm_delete') }}">{{ __('admin.common.delete') }}</x-admin.row-action>
                        @endif
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-3 py-6 text-center text-grey-600">{{ __('admin.common.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
</div>
