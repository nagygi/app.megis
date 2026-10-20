{{--
    A menu that opens from a button: the row menus of the admin lists, the
    avatar menus. Fixed to the viewport, so a scrolling table cannot clip it.
    It closes on Esc (the focus goes back to the button), on a click outside
    or on an item, and when anything scrolls; it slides in and is gone at once.
    - label: the button's accessible name; without a "trigger" slot the button
      shows the "more" icon;
    - width: the menu's width in px; align: the button's right (default) or
      left edge the menu lines up with;
    - triggerClass: the button's classes with a "trigger" slot;
    - id: the menu's id, for aria-controls from tests and links (random without).
--}}
@props([
    'label',
    'id' => null,
    'width' => 240,
    'align' => 'right',
    'triggerClass' => 'press grid size-11 place-items-center rounded-pill hover:bg-grey-100',
])

@php
    $menuId = $id ?? 'menu-'.\Illuminate\Support\Str::random(8);
@endphp

<div
    x-data="{
        open: false,
        top: 0,
        left: 0,
        init() {
            this.onScroll = () => this.close();
            document.addEventListener('scroll', this.onScroll, { capture: true, passive: true });
        },
        destroy() {
            document.removeEventListener('scroll', this.onScroll, { capture: true });
        },
        toggle() {
            this.open ? this.close() : this.show();
        },
        show() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const edge = {{ $align === 'left' ? 'rect.left' : 'rect.right - '.(int) $width }};
            this.top = rect.bottom + 6;
            this.left = Math.max(8, Math.min(window.innerWidth - {{ (int) $width }} - 8, edge));
            this.open = true;
        },
        close(returnFocus = false) {
            if (! this.open) {
                return;
            }
            this.open = false;
            if (returnFocus) {
                this.$refs.trigger.focus();
            }
        },
    }"
    @keydown.escape.window="close(true)"
    @click.outside="close()"
    @resize.window="close()"
    {{ $attributes->class(['inline-block text-left']) }}
>
    <button
        type="button"
        x-ref="trigger"
        @isset($trigger) class="{{ $triggerClass }}" @else class="press grid size-11 place-items-center rounded-pill hover:bg-grey-100" aria-label="{{ $label }}" title="{{ $label }}" @endisset
        aria-controls="{{ $menuId }}"
        :aria-expanded="open"
        @click="toggle()"
    >
        @isset($trigger)
            {{ $trigger }}
            <span class="sr-only">{{ $label }}</span>
        @else
            <x-megis.icon name="more" class="size-5" />
        @endisset
    </button>

    <div
        id="{{ $menuId }}"
        x-show="open"
        x-cloak
        x-transition:enter="transition-[translate] duration-200 ease-brand-out"
        x-transition:enter-start="-translate-y-1"
        @click="close()"
        :style="`top: ${top}px; left: ${left}px; width: {{ (int) $width }}px`"
        class="fixed z-40 flex flex-col gap-0.5 rounded-card border border-grey-250 bg-white p-2 text-charcoal"
    >
        {{ $slot }}
    </div>
</div>
