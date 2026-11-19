{{--
    An explanation in a bubble next to a label, a term or a locked tab (a
    toggletip). It opens on hover and on focus, a tap or a click keeps it open
    until the next one; Esc, a click outside and any scrolling close it. Fixed
    to the viewport, so a scrolling row (the tabs, the stage line) cannot clip
    it. The button is described by the bubble, so a screen reader reads the
    explanation on focus too.
    - text: the explanation (or the slot);
    - label: the info button's accessible name ("Mit jelent?" by default);
    - with a "trigger" slot the button shows that instead of the info sign,
      with triggerClass (a locked tab).
--}}
@props([
    'text' => null,
    'label' => null,
    'triggerClass' => 'press -m-2 grid size-8 shrink-0 place-items-center rounded-pill text-grey-600 hover:text-charcoal',
])

@php
    $bubbleId = 'info-'.\Illuminate\Support\Str::random(8);
@endphp

<span
    x-data="{
        open: false,
        pinned: false,
        top: 0,
        left: 0,
        init() {
            this.onScroll = () => this.close();
            document.addEventListener('scroll', this.onScroll, { capture: true, passive: true });
        },
        destroy() {
            document.removeEventListener('scroll', this.onScroll, { capture: true });
        },
        show() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const width = Math.min(288, window.innerWidth - 16);
            this.top = rect.bottom + 6;
            this.left = Math.max(8, Math.min(window.innerWidth - width - 8, rect.left + rect.width / 2 - width / 2));
            this.open = true;
        },
        close() {
            this.open = false;
            this.pinned = false;
        },
        toggle() {
            this.pinned = ! this.pinned;
            this.pinned ? this.show() : this.close();
        },
    }"
    @keydown.escape="close()"
    @click.outside="close()"
    @resize.window="close()"
    {{ $attributes->class(['inline-flex align-middle']) }}
>
    <button
        type="button"
        x-ref="trigger"
        class="{{ $triggerClass }}"
        @unless (isset($trigger)) aria-label="{{ $label ?? __('ui.info') }}" @endunless
        aria-describedby="{{ $bubbleId }}"
        :aria-expanded="open"
        @click="toggle()"
        @mouseenter="show()"
        @mouseleave="pinned || close()"
        @focus="show()"
        @blur="close()"
    >
        @isset($trigger)
            {{ $trigger }}
        @else
            <x-megis.icon name="info" class="size-4" />
        @endisset
    </button>

    <span
        id="{{ $bubbleId }}"
        role="tooltip"
        x-show="open"
        x-cloak
        :style="`top: ${top}px; left: ${left}px`"
        class="fixed z-50 w-72 max-w-[calc(100vw-1rem)] rounded-field bg-charcoal px-3 py-2 text-left text-sm font-normal leading-snug whitespace-normal text-cream"
    >{{ $text ?? $slot }}</span>
</span>
