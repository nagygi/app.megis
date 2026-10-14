{{-- Hamburger menu at the end of an admin list row (NFR · 3). --}}
@props(['label'])

<div x-data="{ open: false }" class="relative inline-block text-left" @keydown.escape="open = false">
    <button
        type="button"
        class="rounded-pill p-1.5 hover:bg-grey-100"
        aria-haspopup="true"
        :aria-expanded="open"
        @click="open = ! open"
    >
        <x-megis.icon name="menu" class="size-4" :label="$label" />
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        @click="open = false"
        class="absolute right-0 z-20 mt-1 flex w-60 flex-col gap-0.5 rounded-card border border-grey-250 bg-white p-2 text-sm"
    >
        {{ $slot }}
    </div>
</div>
