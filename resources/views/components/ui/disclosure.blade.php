{{--
    A section that opens on a click: a white card whose summary carries a
    chevron that turns when open (the browser's own <details>, so keyboards
    and screen readers know it). summary: the line that opens it; open: open
    from the start.
--}}
@props(['summary', 'open' => false])

<details {{ $attributes->class(['group rounded-card bg-white']) }} @if ($open) open @endif>
    <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 rounded-card px-5 py-3 font-semibold hover:bg-paper [&::-webkit-details-marker]:hidden">
        <span>{{ $summary }}</span>
        <x-megis.icon name="chevron-down" class="shrink-0 transition-transform duration-200 group-open:rotate-180" />
    </summary>
    <div class="px-5 pb-5">
        {{ $slot }}
    </div>
</details>
