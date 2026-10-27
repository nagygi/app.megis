{{--
    "A végén": what the diagnosis gives, grey and not clickable (23, question 2;
    25 · 25.3), under a line as in the prototype (V1, T2). Without Brand in
    the MVP: one dashed ring, the biggest gap, the chapters, and the
    capabilities while they are measured (NM-12); for a peer the Útravaló.
    Without a diagnosis yet (the home) the title has no date. Grey-600, not
    the prototype's lighter grey, for the text contrast.
--}}
@props(['main' => null, 'peer' => false])

@php
    $rows = $peer
        ? [[__('diagnosis.end.takeaway'), __('diagnosis.end.takeaway_help')]]
        : array_values(array_filter([
            [__('diagnosis.end.gap'), __('diagnosis.end.gap_help')],
            $main?->isSnapshot() ? [__('diagnosis.end.one_page'), null] : [__('diagnosis.end.chapters'), __('diagnosis.end.chapters_help')],
            $main?->measuresCapabilities() ? [__('diagnosis.end.capabilities'), __('diagnosis.end.capabilities_help')] : null,
        ]));
@endphp

<section {{ $attributes->class(['flex flex-col gap-3 border-t border-grey-250 pt-4 text-grey-600']) }} aria-labelledby="end-picture">
    <h2 id="end-picture" class="text-xs font-medium tracking-[.08em] uppercase">
        {{ $main ? __('diagnosis.end.title', ['date' => $main->publishes_on->format('m.d.')]) : __('diagnosis.end.title_open') }}
    </h2>

    @unless ($peer)
        <div class="flex flex-col items-center gap-1">
            <svg viewBox="0 0 72 72" class="size-18" aria-hidden="true">
                <circle cx="36" cy="36" r="28" fill="none" class="stroke-grey-350" stroke-width="2" stroke-dasharray="4 4" />
                <text x="36" y="41" text-anchor="middle" font-size="14" font-weight="700" class="fill-grey-400">?</text>
            </svg>
            <p class="text-sm">{{ __('diagnosis.end.ring') }}</p>
        </div>
    @endunless

    <ul class="flex flex-col">
        @foreach ($rows as [$title, $help])
            <li class="border-b border-grey-200 py-2.5">
                <p class="text-sm font-semibold">{{ $title }}</p>
                @if ($help)
                    <p class="text-xs">{{ $help }}</p>
                @endif
            </li>
        @endforeach
    </ul>
</section>
