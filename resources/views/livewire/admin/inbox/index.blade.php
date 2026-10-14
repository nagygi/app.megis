@php([$sortField, $sortDirection] = $this->currentSort())

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.inbox.title')" />

    <x-admin.list-toolbar>
        <x-admin.filter name="state" :label="__('admin.inbox.state')" :all-label="__('admin.inbox.open')" :options="['closed' => __('admin.inbox.closed')]" />
        <x-admin.filter name="type" :label="__('admin.inbox.type')" :options="$types" />
        <x-admin.filter name="organization" :label="__('admin.inbox.organization')" :options="$organizations" />
        <x-admin.filter name="contact" :label="__('admin.inbox.contact')" :all-label="__('admin.inbox.contact_me')" :options="$contacts" />
    </x-admin.list-toolbar>

    @if ($items->isEmpty() && ! $showClosed)
        <div class="flex flex-col items-center gap-3 rounded-card border border-grey-250 bg-white px-6 py-12 text-center">
            <x-megis.symbol name="tree-of-life" class="size-16 text-green" />
            <p class="text-lg font-semibold">{{ __('admin.inbox.empty') }}</p>
        </div>
    @else
        <x-admin.table :paginator="$items">
            <x-slot:head>
                <x-admin.th field="type" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.inbox.type') }}</x-admin.th>
                <x-admin.th field="organization" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.inbox.diagnosis') }}</x-admin.th>
                <x-admin.th>{{ __('admin.inbox.summary') }}</x-admin.th>
                <x-admin.th field="age" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.inbox.age') }}</x-admin.th>
                @if ($showClosed)
                    <x-admin.th field="closed" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.inbox.closed_by') }}</x-admin.th>
                @else
                    <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
                @endif
            </x-slot:head>

            @foreach ($items as $item)
                <tr wire:key="inbox-{{ $item->id }}">
                    <td class="px-3 py-2"><x-ui.badge>{{ $item->type->label() }}</x-ui.badge></td>
                    <td class="px-3 py-2">
                        @if ($item->diagnosis)
                            <a href="{{ route('admin.diagnoses.show', $item->diagnosis) }}" class="font-semibold text-charcoal">{{ $item->organization?->name }}@if ($item->diagnosis->team) · {{ $item->diagnosis->team->name }}@endif</a>
                        @else
                            {{ $item->organization?->name ?? __('admin.common.none') }}
                        @endif
                    </td>
                    <td class="px-3 py-2">{{ $item->summary }}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-grey-700" title="{{ $item->created_at->format('Y.m.d. H:i') }}">{{ $item->created_at->diffForHumans() }}</td>
                    @if ($showClosed)
                        <td class="px-3 py-2 whitespace-nowrap text-grey-700">
                            {{ $item->closed_at->format('Y.m.d. H:i') }}
                            <span class="block text-sm">{{ $item->closer?->name ?? __('admin.inbox.closed_automatically') }}</span>
                        </td>
                    @else
                        <td class="px-3 py-2 text-right">
                            <x-ui.button tone="outline" size="sm" wire:click="close({{ $item->id }})" aria-label="{{ __('admin.inbox.close') }}: {{ $item->summary }}">{{ __('admin.inbox.close') }}</x-ui.button>
                        </td>
                    @endif
                </tr>
            @endforeach
        </x-admin.table>
    @endif
</div>
