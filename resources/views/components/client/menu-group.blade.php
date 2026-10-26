{{--
    A group of the customer menu that opens in place (M1): Riportok, Fejezetek,
    Tárak. The browser's own <details>, so keyboards and screen readers know it;
    the slot holds the nested x-client.menu-link rows.
--}}
@props(['title', 'meta' => null, 'symbol', 'open' => false])

<li class="border-b border-grey-700">
    <details class="group" @if ($open) open @endif>
        <summary class="press flex min-h-11 cursor-pointer list-none items-center gap-3 py-3.5 hover:text-yellow [&::-webkit-details-marker]:hidden">
            <x-megis.symbol :name="$symbol" class="size-6 text-yellow" />
            <span class="min-w-0 flex-1">
                <span class="block text-base leading-tight font-semibold">{{ $title }}</span>
                @if (filled($meta))
                    <span class="mt-0.5 block text-xs text-grey-400">{{ $meta }}</span>
                @endif
            </span>
            <x-megis.icon name="chevron-down" class="shrink-0 text-grey-400 transition-transform duration-200 group-open:rotate-180" />
        </summary>
        <ul class="flex flex-col pb-2.5 pl-9">
            {{ $slot }}
        </ul>
    </details>
</li>
