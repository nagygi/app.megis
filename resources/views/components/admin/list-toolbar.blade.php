{{-- Search field and filters above an admin list (NFR · 3). Filters go in the slot. --}}
@props(['searchLabel' => null])

<div {{ $attributes->class(['mb-4 flex flex-wrap items-end gap-3']) }}>
    <div class="flex min-w-60 flex-1 flex-col gap-1.5">
        <label for="list-search" class="text-sm font-medium">{{ $searchLabel ?? __('admin.common.search') }}</label>
        <input
            id="list-search"
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('admin.common.search_placeholder') }}"
            class="w-full rounded-field border border-grey-350 bg-white px-3.5 py-2 text-base focus:border-charcoal"
        >
    </div>
    {{ $slot }}
</div>
