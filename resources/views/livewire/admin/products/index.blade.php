@php
    [$sortField, $sortDirection] = $this->currentSort();
    // An empty list says "Nincs találat." only when the search hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.products.title')">
        <x-slot:actions>
            <x-ui.button icon="plus" :href="route('admin.products.create')">{{ __('admin.products.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar />

    <x-admin.table :paginator="$products">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.products.name') }}</x-admin.th>
            <x-admin.th>{{ __('admin.products.description') }}</x-admin.th>
            <x-admin.th field="organizations" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.products.organizations') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($products as $product)
            <tr wire:key="product-{{ $product->id }}">
                <td class="px-3 py-2"><a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-charcoal">{{ $product->name }}</a></td>
                <td class="px-3 py-2 text-grey-700">{{ $product->description ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">{{ $product->organizations_count }}</td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $product->name])">
                        <x-admin.row-action :href="route('admin.products.edit', $product)">{{ __('admin.common.edit') }}</x-admin.row-action>
                        @if ($product->organizations_count === 0)
                            <x-admin.row-action tone="danger" wire:click="delete({{ $product->id }})" wire:confirm="{{ __('admin.products.confirm_delete') }}" data-confirm-tone="danger">{{ __('admin.common.delete') }}</x-admin.row-action>
                        @endif
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="4">
                {{ $filtering ? __('admin.common.empty') : __('admin.products.empty') }}
                @unless ($filtering)
                    <x-slot:action>
                        <x-ui.button tone="outline" icon="plus" :href="route('admin.products.create')">{{ __('admin.products.create') }}</x-ui.button>
                    </x-slot:action>
                @endunless
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
