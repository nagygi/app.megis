{{-- Search field and filters above an admin list (NFR · 3). Filters go in the slot. --}}
@props(['searchLabel' => null])

<div {{ $attributes->class(['mb-4 flex flex-wrap items-end gap-3']) }}>
    <div class="flex min-w-60 flex-1 flex-col gap-1.5">
        <label for="list-search" class="text-sm font-medium">{{ $searchLabel ?? __('admin.common.search') }}</label>
        <div class="relative">
            <x-megis.icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-grey-600" />
            <input
                id="list-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('admin.common.search_placeholder') }}"
                class="field w-full pl-10"
            >
        </div>
    </div>
    {{ $slot }}
</div>
