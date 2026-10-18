@php([$sortField, $sortDirection] = $this->currentSort())

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.value_categories.title')">
        <x-slot:actions>
            <x-ui.button :href="route('admin.value-categories.create')">{{ __('admin.value_categories.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar />

    <x-admin.table :paginator="$categories">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.value_categories.name') }}</x-admin.th>
            <x-admin.th field="higher_order" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.value_categories.higher_order') }}</x-admin.th>
            <x-admin.th>{{ __('admin.value_categories.description') }}</x-admin.th>
            <x-admin.th field="values" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.value_categories.values') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($categories as $category)
            <tr wire:key="category-{{ $category->id }}">
                <td class="px-3 py-2"><a href="{{ route('admin.value-categories.edit', $category) }}" class="font-semibold text-charcoal">{{ $category->label() }}</a></td>
                <td class="px-3 py-2 text-grey-700">{{ $category->higher_order ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2 text-grey-700">{{ $category->description ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">
                    <a href="{{ route('admin.values.index', ['szuro' => ['category' => $category->id]]) }}" class="text-charcoal">{{ $category->values_count }}</a>
                </td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $category->name])">
                        <x-admin.row-action :href="route('admin.value-categories.edit', $category)">{{ __('admin.common.edit') }}</x-admin.row-action>
                        @if ($category->values_count === 0)
                            <x-admin.row-action wire:click="delete({{ $category->id }})" wire:confirm="{{ __('admin.value_categories.confirm_delete') }}">{{ __('admin.common.delete') }}</x-admin.row-action>
                        @endif
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-3 py-6 text-center text-grey-600">{{ __('admin.common.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
</div>
