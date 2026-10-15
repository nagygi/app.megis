{{-- Hamburger menu at the end of an admin list row (NFR · 3). --}}
@props(['label'])

{{-- The menu is position:fixed so the table's overflow container cannot clip it. --}}
<div
    x-data="{
        open: false,
        top: 0,
        left: 0,
        toggle() {
            if (! this.open) {
                const rect = this.$refs.trigger.getBoundingClientRect();
                this.top = rect.bottom + 4;
                this.left = Math.max(8, rect.right - 240);
            }
            this.open = ! this.open;
        },
    }"
    class="inline-block text-left"
    @keydown.escape="open = false"
    @scroll.window="open = false"
    @resize.window="open = false"
>
    <button
        type="button"
        x-ref="trigger"
        class="rounded-pill p-1.5 hover:bg-grey-100"
        aria-haspopup="true"
        :aria-expanded="open"
        @click="toggle()"
    >
        <x-megis.icon name="menu" class="size-4" :label="$label" />
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        @click="open = false"
        :style="`top: ${top}px; left: ${left}px`"
        class="fixed z-20 flex w-60 flex-col gap-0.5 rounded-card border border-grey-250 bg-white p-2 text-sm"
    >
        {{ $slot }}
    </div>
</div>
