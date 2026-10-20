{{--
    The step to the next page at the bottom of a chapter, an item or a topic,
    the same everywhere: on the left where we are ("2 / 4 · Értékek") or the
    way back, on the right the next page as an outline pill with a chevron.
--}}
@props([
    'label',
    'position' => null,
    'back' => null,
    'backLabel' => null,
    'next' => null,
    'nextLabel' => null,
])

<nav aria-label="{{ $label }}" {{ $attributes->class(['mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-grey-250 pt-4']) }}>
    <div>
        @if ($back)
            <x-ui.button tone="ghost" size="sm" icon="chevron-left" class="-ml-1.5" :href="$back">{{ $backLabel }}</x-ui.button>
        @elseif ($position)
            <span class="text-sm text-grey-700">{{ $position }}</span>
        @endif
    </div>
    @if ($next)
        <x-ui.button tone="outline" size="sm" icon-right="chevron-right" class="ml-auto" :href="$next">{{ $nextLabel }}</x-ui.button>
    @endif
</nav>
