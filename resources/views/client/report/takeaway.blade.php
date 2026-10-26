{{-- T6 · Útravaló (29 · 29.1): experience only, no PHI, no other name. --}}
@php
    $whole = fn ($number) => $number === null ? '–' : (int) floor($number + 0.5);
    $date = \Illuminate\Support\Carbon::parse($publishedOn)->format('m.d.');
    $position = fn ($number) => $number === null ? null : round(($number - 1) / 5 * 100);
@endphp
<x-layouts::client :title="__('takeaway.title')" color="green" :subtitle="__('takeaway.subtitle', ['date' => $date, 'subject' => $takeaway['subject_name']])" :back="$preview ? null : route('client.diagnosis.show', [$organization, $diagnosis])" :back-label="__('diagnosis.home_back')">
    <div class="flex max-w-2xl flex-col gap-6">
        <p class="text-lg">{{ __('takeaway.lead') }}</p>

        <ol class="flex flex-col gap-3">
            @if ($takeaway['strength'])
                <li class="rounded-card bg-green p-5 text-cream on-dark">
                    <p class="text-xs font-semibold uppercase tracking-wide">1 · {{ __('takeaway.strength') }}</p>
                    <p class="text-lg font-semibold">{{ $takeaway['strength']['name'] }}</p>
                    <p>{{ $takeaway['strength']['situations'] !== null
                        ? __('takeaway.strength_situations', ['score' => $whole($takeaway['strength']['situations']), 'count' => $takeaway['strength']['situation_count']])
                        : __('takeaway.strength_start', ['score' => $whole($takeaway['strength']['start'])]) }}</p>
                </li>
            @endif
            @if ($takeaway['own_value'])
                <li class="rounded-card bg-charcoal p-5 text-cream on-dark">
                    <p class="text-xs font-semibold uppercase tracking-wide">2 · {{ __('takeaway.own_value') }}</p>
                    <p class="text-lg font-semibold">{{ $takeaway['own_value']['name'] }}</p>
                    <p>{{ __('takeaway.own_value_line', ['count' => $takeaway['own_value']['team_count']]) }}</p>
                </li>
            @endif
            @if ($takeaway['self_gap'])
                <li class="rounded-card bg-coral p-5 text-black">
                    <p class="text-xs font-semibold uppercase tracking-wide">3 · {{ __('takeaway.self_gap') }}</p>
                    <p class="text-lg font-semibold">{{ $takeaway['self_gap']['name'] }}</p>
                    <p>{{ __('takeaway.self_gap_line', ['start' => $whole($takeaway['self_gap']['start']), 'situations' => $whole($takeaway['self_gap']['situations'])]) }}</p>
                </li>
            @endif
            @if ($takeaway['others_gap'])
                <li class="rounded-card bg-yellow p-5 text-charcoal">
                    <p class="text-xs font-semibold uppercase tracking-wide">4 · {{ __('takeaway.others_gap') }}</p>
                    <p class="text-lg font-semibold">{{ $takeaway['others_gap']['name'] }}</p>
                    <p>{{ __('takeaway.others_gap_line', ['you' => $whole($takeaway['others_gap']['start']), 'others' => $whole($takeaway['others_gap']['others'])]) }}</p>
                </li>
            @endif
        </ol>

        {{-- The scale: grey circle at the start, charcoal dot in the situations, green mark the others --}}
        <x-ui.card as="section" class="flex flex-col gap-3" aria-labelledby="takeaway-scale">
            <h2 id="takeaway-scale" class="text-lg font-semibold">{{ __('takeaway.scale') }}</h2>
            <p class="flex flex-wrap gap-4 text-xs text-grey-700" aria-hidden="true">
                <span class="flex items-center gap-1"><span class="size-3 rounded-pill border-2 border-grey-350"></span>{{ __('takeaway.legend.start') }}</span>
                <span class="flex items-center gap-1"><span class="size-3 rounded-pill bg-charcoal"></span>{{ __('takeaway.legend.situations') }}</span>
                <span class="flex items-center gap-1"><span class="h-3 w-1 bg-green"></span>{{ __('takeaway.legend.others') }}</span>
            </p>
            <ul class="flex flex-col gap-4">
                @foreach ($takeaway['rows'] as $row)
                    <li class="flex flex-col gap-1">
                        <p @class(['flex justify-between text-sm', 'font-semibold text-coral-ink' => $row['lower']])>
                            <span>{{ $row['name'] }}</span>
                            <span class="tabular-nums">{{ __('takeaway.row_numbers', ['start' => $whole($row['start']), 'situations' => $whole($row['situations']), 'others' => $whole($row['others'])]) }}</span>
                        </p>
                        <div @class(['relative h-2 rounded-pill', 'bg-coral-light' => $row['lower'], 'bg-grey-200' => ! $row['lower']]) aria-hidden="true">
                            @if ($row['start'] !== null)
                                <span class="absolute top-1/2 size-3 -translate-x-1/2 -translate-y-1/2 rounded-pill border-2 border-grey-350 bg-white" style="left: {{ $position($row['start']) }}%"></span>
                            @endif
                            @if ($row['situations'] !== null)
                                <span class="absolute top-1/2 size-3 -translate-x-1/2 -translate-y-1/2 rounded-pill bg-charcoal" style="left: {{ $position($row['situations']) }}%"></span>
                            @endif
                            @if ($row['others'] !== null)
                                <span class="absolute -top-1 h-4 w-1 -translate-x-1/2 bg-green" style="left: {{ $position($row['others']) }}%"></span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        @if ($recommendation)
            <section class="flex flex-col gap-1 rounded-card bg-yellow-pale p-5" aria-labelledby="takeaway-recommendation">
                <h2 id="takeaway-recommendation" class="text-lg font-semibold">{{ __('takeaway.recommendation') }}</h2>
                <p>{{ $recommendation['text'] }}</p>
            </section>
        @endif

        @unless ($preview)
            <x-ui.link-row :href="route('client.mirror.record', $organization)" :title="__('takeaway.mirror_again')" />
            <div class="flex flex-wrap gap-2">
                @if (config('megis.pdf_downloads') && Route::has('client.diagnosis.takeaway.pdf'))
                    <x-ui.button tone="outline" size="sm" icon="document" :href="route('client.diagnosis.takeaway.pdf', [$organization, $diagnosis])">{{ __('takeaway.pdf') }}</x-ui.button>
                @endif
                <x-ui.button tone="ghost" size="sm" icon-right="chevron-right" :href="route('client.values', $organization)">{{ __('client.menu.value_library') }}</x-ui.button>
            </div>
        @endunless
    </div>
</x-layouts::client>
