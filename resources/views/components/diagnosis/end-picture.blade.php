{{--
    The end: what the diagnosis gives, grey and not clickable (23 · 23.1;
    25 · 25.3), under a line as in the prototype (V1, T2).
    - The leader: "Elkészül a diagnózisotok · {publishing}", without Brand in
      the MVP one dashed ring, the biggest gap, the capabilities while they
      are measured (NM-12), and the processes when the product has them.
      Without a diagnosis yet (the home) the title has no date.
    - A peer: the Útravaló alone; the day it is done is the last row of
      their step line (step-list tail).
    Grey-600, not the prototype's lighter grey, for the text contrast.
--}}
@props(['main' => null, 'peer' => false])

@php
    $rows = $peer ? [] : array_values(array_filter([
        [__('diagnosis.end.gap'), __('diagnosis.end.gap_help')],
        $main?->measuresCapabilities() ? [__('diagnosis.end.capabilities'), __('diagnosis.end.capabilities_help')] : null,
        in_array(\App\Enums\DiagnosisStep::Processes, $main?->leaderSteps() ?? [], true) ? [__('diagnosis.end.processes'), __('diagnosis.end.processes_help')] : null,
    ]));
@endphp

<section {{ $attributes->class(['flex flex-col gap-3 border-t border-grey-250 pt-4 text-grey-600']) }} aria-labelledby="end-picture">
    @if ($peer)
        <div>
            <h2 id="end-picture" class="text-sm font-semibold">{{ __('diagnosis.end.takeaway') }}</h2>
            <p class="text-xs">{{ __('diagnosis.end.takeaway_help') }}</p>
        </div>
    @else
        <h2 id="end-picture" class="text-xs font-medium tracking-[.08em] uppercase">
            {{ $main ? __('diagnosis.end.title', ['date' => $main->publishes_on->format('m.d.')]) : __('diagnosis.end.title_open') }}
        </h2>

        <div class="flex flex-col items-center gap-1">
            <svg viewBox="0 0 72 72" class="size-18" aria-hidden="true">
                <circle cx="36" cy="36" r="28" fill="none" class="stroke-grey-350" stroke-width="2" stroke-dasharray="4 4" />
                <text x="36" y="41" text-anchor="middle" font-size="14" font-weight="700" class="fill-grey-400">?</text>
            </svg>
            <p class="text-sm">{{ __('diagnosis.end.ring') }}</p>
        </div>

        <ul class="flex flex-col">
            @foreach ($rows as [$title, $help])
                <li class="border-b border-grey-200 py-2.5">
                    <p class="text-sm font-semibold">{{ $title }}</p>
                    <p class="text-xs">{{ $help }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</section>
