{{--
    V12, V12·b · the leader's home after publishing (26 · 26.3), from the frozen report.
    The admin's preview includes it too (21 · 21.1, client/report/home): no wire: here,
    and every report link goes through $reportUrl.
--}}
@use('App\Services\Report\ReportText')
@php
    $organization = $main->diagnosis->organization;
    $diagnosis = $main->diagnosis;
    $ring = $report['ring'];
    $gap = $report['biggest_gap'];
    $snapshot = $report['meta']['snapshot'];
@endphp
<div class="flex max-w-2xl flex-col gap-6">
    @unless ($preview)
        <x-ui.flash />
    @endunless

    <x-ui.card as="section" padding="lg" class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left" aria-label="{{ __('report.ring.label') }}">
        <x-report.ring :percent="$ring['percent']" />
        <div class="flex flex-col items-center gap-1 sm:items-start">
            <p class="text-lg font-semibold">{{ __('report.ring.label') }}</p>
            <p class="text-grey-700">{{ __('diagnosis.published_home.points', ['points' => $ring['points'], 'max' => $ring['max']]) }}</p>
            @unless ($preview)
                <x-ui.button tone="ghost" size="sm" :href="route('client.wisdom.show', [$organization, 'szazalek-es-pontok'])">{{ __('report.ring.what') }}</x-ui.button>
            @endunless
        </div>
    </x-ui.card>

    @if ($gap)
        <a href="{{ $reportUrl('item', [\App\Services\Report\ReportService::SLUGS[$gap['chapter']], $gap['id']]) }}" class="press on-dark flex items-start justify-between gap-3 rounded-card bg-charcoal p-5 text-cream no-underline hover:bg-grey-700">
            <span class="flex flex-col gap-2">
                <span class="text-xs font-semibold tracking-wide text-coral-light uppercase">{{ __('report.biggest_gap') }}</span>
                <span>{{ __('report.biggest_gap_sentence', ['label' => $gap['label'], 'sentence' => ReportText::gap($gap, $report['meta']) ?? '']) }}</span>
            </span>
            <x-megis.icon name="chevron-right" class="mt-6 shrink-0" />
        </a>
    @elseif ($snapshot && $report['crack'])
        <a href="{{ $reportUrl('show') }}" class="press on-dark flex items-start justify-between gap-3 rounded-card bg-charcoal p-5 text-cream no-underline hover:bg-grey-700">
            <span class="flex flex-col gap-2">
                <span class="text-xs font-semibold tracking-wide text-coral-light uppercase">{{ __('report.breakpoint_marker') }}</span>
                <span>{{ $report['crack']['text'] }}</span>
            </span>
            <x-megis.icon name="chevron-right" class="mt-6 shrink-0" />
        </a>
    @elseif (! $report['meta']['shows_experience'])
        {{-- Below the threshold there is no gap: no row is named (Extra inputok 1). --}}
        <div class="on-dark flex flex-col gap-2 rounded-card bg-charcoal p-5 text-cream">
            <span class="text-xs font-semibold tracking-wide text-coral-light uppercase">{{ __('report.biggest_gap') }}</span>
            <span>{{ ReportText::missingPeers($report['meta']) }}</span>
        </div>
    @endif

    {{-- Képesség (NM-12): what to build on and what is missing, once the mégis approved them --}}
    @if (($capabilities['rows'] ?? []) !== [])
        <x-report.capability-card :overview="$capabilities" :href="route('client.capabilities', $organization)" />
    @endif

    {{-- Mélyítés, live (26 · 26.3) --}}
    <ul class="flex flex-col gap-2">
        <li>
            @unless ($snapshot)
                <x-ui.link-row tone="green" :href="$reportUrl('internal')" :title="__('diagnosis.published_home.internal')" />
            @else
                <x-ui.link-row tone="green" :href="$reportUrl('show')" :title="__('diagnosis.published_home.snapshot')" />
            @endunless
        </li>
        @if (isset($report['chapters']['brand']))
            <li>
                <x-ui.link-row tone="yellow" :href="$reportUrl('chapter', ['markad-alapja'])" :title="__('diagnosis.published_home.brand')" />
            </li>
        @endif
    </ul>

    {{-- Riportok (26 · 26.3, 28) --}}
    <section class="flex flex-col gap-2" aria-labelledby="reports-title">
        <h2 id="reports-title" class="text-lg font-semibold">{{ __('report.menu.reports') }}</h2>
        <ul class="flex flex-col gap-2">
            @foreach ($reports as $reportMain)
                {{-- This diagnosis with the report's date: in the preview it is not published yet (21 · 21.1). --}}
                @php($isThis = $reportMain->is($main))
                <x-ui.card as="li" padding="none" class="flex flex-wrap items-center justify-between gap-2 py-2 pr-2 pl-5">
                    <span>{{ $reportMain->product_name }} · {{ $isThis ? \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('Y.m.d.') : $reportMain->published_at?->format('Y.m.d.') }}</span>
                    <span class="flex flex-wrap gap-1">
                        @if ($isThis || ! $preview)
                            <x-ui.button tone="ghost" size="sm" icon-right="chevron-right" :href="$isThis ? $reportUrl('show') : route('client.report.show', [$organization, $reportMain->diagnosis])">{{ __('diagnosis.published_home.open') }}</x-ui.button>
                        @endif
                        @if (! $preview && config('megis.pdf_downloads') && Route::has('client.report.pdf'))
                            <x-ui.button tone="ghost" size="sm" icon="document" :href="route('client.report.pdf', [$organization, $reportMain->diagnosis])">{{ __('diagnosis.published_home.pdf') }}</x-ui.button>
                        @endif
                    </span>
                </x-ui.card>
            @endforeach
        </ul>
    </section>

    {{-- Értékmérés-teaser (26, question 4) --}}
    <x-ui.card as="section" class="flex flex-col gap-3" aria-labelledby="measure-title">
        <h2 id="measure-title" class="text-lg font-semibold">{{ __('diagnosis.published_home.measure_title') }}</h2>
        <p class="text-grey-700">{{ __('diagnosis.published_home.measure_text') }}</p>
        @if (filled(config('megis.booking_url')))
            <x-ui.button tone="outline" class="self-start" :href="config('megis.booking_url')" target="_blank" rel="noopener">
                {{ __('diagnosis.published_home.measure_action') }}
                <span class="sr-only">({{ __('ui.new_tab') }})</span>
            </x-ui.button>
        @endif
    </x-ui.card>

    @foreach ($otherRoles as $peerMain)
        <x-ui.link-row :href="route('client.diagnosis.show', [$organization, $peerMain->diagnosis])" :title="__('diagnosis.peer_link', ['subject' => $peerMain->subjectName()])" />
    @endforeach
</div>
