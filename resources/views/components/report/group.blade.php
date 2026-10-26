{{--
    A group of rows under its green capital label (prototype .lbl), with a
    link on the right ("összefoglaló ›") or the "hamarosan" pill.
--}}
@props(['label', 'href' => null, 'linkLabel' => null, 'soon' => false])

<section {{ $attributes->class(['flex flex-col']) }}>
    <h2 class="flex items-center justify-between gap-3 text-xs font-semibold tracking-[.08em] text-green uppercase">
        @if ($href)
            <a href="{{ $href }}" class="flex min-h-6 flex-1 items-center justify-between gap-3 text-green no-underline hover:underline">
                <span>{{ $label }}</span>
                @if ($linkLabel)
                    <span class="inline-flex items-center gap-0.5 normal-case tracking-normal">{{ $linkLabel }} <x-megis.icon name="chevron-right" class="size-3.5" /></span>
                @endif
            </a>
        @else
            <span>{{ $label }}</span>
        @endif
        @if ($soon)
            <x-report.soon />
        @endif
    </h2>
    {{ $slot }}
</section>
