{{--
    "A végén": what the diagnosis gives, grey and not clickable (23, question 2;
    25 · 25.3). Without Brand in the MVP: one ring, the biggest gap, the
    chapters, and the capabilities while they are measured (NM-12); for a
    peer the Útravaló.
--}}
@props(['main', 'peer' => false])

<section {{ $attributes->class(['flex flex-col gap-4 rounded-card border border-dashed border-grey-350 p-5 text-grey-700']) }} aria-labelledby="end-picture">
    <h2 id="end-picture" class="text-sm font-semibold uppercase">{{ __('diagnosis.end.title', ['date' => $main->publishes_on->format('m.d.')]) }}</h2>

    @if ($peer)
        <div class="flex flex-col gap-1">
            <p class="font-semibold">{{ __('diagnosis.end.takeaway') }}</p>
            <p class="text-sm">{{ __('diagnosis.end.takeaway_help') }}</p>
        </div>
    @else
        <div class="flex items-center gap-4">
            <svg viewBox="0 0 64 64" class="size-16 shrink-0" aria-hidden="true">
                <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="6" opacity="0.35" />
                <text x="32" y="40" text-anchor="middle" font-size="24" font-weight="800" fill="currentColor">?</text>
            </svg>
            <p class="font-semibold">{{ __('diagnosis.end.ring') }}</p>
        </div>
        <div class="flex flex-col gap-1">
            <p class="font-semibold">{{ __('diagnosis.end.gap') }}</p>
            <p class="text-sm">{{ __('diagnosis.end.gap_help') }}</p>
        </div>
        <div class="flex flex-col gap-1">
            <p class="font-semibold">{{ $main->isSnapshot() ? __('diagnosis.end.one_page') : __('diagnosis.end.chapters') }}</p>
            @unless ($main->isSnapshot())
                <p class="text-sm">{{ __('diagnosis.end.chapters_help') }}</p>
            @endunless
        </div>
        @if ($main->measuresCapabilities())
            <div class="flex flex-col gap-1">
                <p class="font-semibold">{{ __('diagnosis.end.capabilities') }}</p>
                <p class="text-sm">{{ __('diagnosis.end.capabilities_help') }}</p>
            </div>
        @endif
    @endif
</section>
