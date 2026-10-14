{{-- V12, V12·b · the leader's home after publishing (26 · 26.3), from the frozen report. --}}
@use('App\Services\Report\ReportText')
@php
    $organization = $main->diagnosis->organization;
    $diagnosis = $main->diagnosis;
    $ring = $report['ring'];
    $gap = $report['biggest_gap'];
    $snapshot = $report['meta']['snapshot'];
@endphp
<div class="flex max-w-2xl flex-col gap-6">
    <section class="flex flex-col items-center gap-3 rounded-card bg-white p-6 text-center sm:flex-row sm:text-left" aria-label="{{ __('report.ring.label') }}">
        <x-report.ring :percent="$ring['percent']" />
        <div class="flex flex-col gap-1">
            <p class="text-lg font-semibold">{{ __('report.ring.label') }}</p>
            <p class="text-grey-700">{{ __('diagnosis.published_home.points', ['points' => $ring['points'], 'max' => $ring['max']]) }}</p>
            <a href="{{ route('client.wisdom.show', [$organization, 'szazalek-es-pontok']) }}" class="text-sm font-semibold">{{ __('report.ring.what') }}</a>
        </div>
    </section>

    @if ($gap)
        <a href="{{ route('client.report.item', [$organization, $diagnosis, \App\Services\Report\ReportService::SLUGS[$gap['chapter']], $gap['id']]) }}" class="flex flex-col gap-2 rounded-card bg-charcoal p-5 text-cream no-underline on-dark">
            <span class="text-xs font-semibold uppercase tracking-wide text-coral-light">{{ __('report.biggest_gap') }}</span>
            <span>{{ __('report.biggest_gap_sentence', ['label' => $gap['label'], 'sentence' => ReportText::gap($gap, $report['meta']) ?? '']) }}</span>
        </a>
    @elseif ($snapshot && $report['crack'])
        <a href="{{ route('client.report.show', [$organization, $diagnosis]) }}" class="flex flex-col gap-2 rounded-card bg-charcoal p-5 text-cream no-underline on-dark">
            <span class="text-xs font-semibold uppercase tracking-wide text-coral-light">{{ __('report.breakpoint_marker') }}</span>
            <span>{{ $report['crack']['text'] }}</span>
        </a>
    @endif

    {{-- Mélyítés, live (26 · 26.3) --}}
    <ul class="flex flex-col gap-2">
        @unless ($snapshot)
            <li>
                <a href="{{ route('client.report.internal', [$organization, $diagnosis]) }}" class="flex items-center justify-between gap-3 rounded-card bg-green px-5 py-4 text-cream no-underline on-dark">
                    <span>{{ __('diagnosis.published_home.internal') }}</span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </a>
            </li>
        @else
            <li>
                <a href="{{ route('client.report.show', [$organization, $diagnosis]) }}" class="flex items-center justify-between gap-3 rounded-card bg-green px-5 py-4 text-cream no-underline on-dark">
                    <span>{{ __('diagnosis.published_home.snapshot') }}</span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </a>
            </li>
        @endunless
        @if (isset($report['chapters']['brand']))
            <li>
                <a href="{{ route('client.report.chapter', [$organization, $diagnosis, 'markad-alapja']) }}" class="flex items-center justify-between gap-3 rounded-card bg-yellow px-5 py-4 text-charcoal no-underline">
                    <span>{{ __('diagnosis.published_home.brand') }}</span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </a>
            </li>
        @endif
    </ul>

    {{-- Riportok (26 · 26.3, 28) --}}
    <section class="flex flex-col gap-2" aria-labelledby="reports-title">
        <h2 id="reports-title" class="text-lg font-semibold">{{ __('report.menu.reports') }}</h2>
        <ul class="flex flex-col gap-2">
            @foreach ($reports as $reportMain)
                <li class="flex flex-wrap items-center justify-between gap-3 rounded-card bg-white px-5 py-3">
                    <span>{{ $reportMain->product_name }} · {{ $reportMain->published_at?->format('Y.m.d.') }}</span>
                    <span class="flex gap-3 text-sm font-semibold">
                        <a href="{{ route('client.report.show', [$organization, $reportMain->diagnosis]) }}">{{ __('diagnosis.published_home.open') }}</a>
                        @if (Route::has('client.report.pdf'))
                            <a href="{{ route('client.report.pdf', [$organization, $reportMain->diagnosis]) }}">PDF</a>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Értékmérés-teaser (26, question 4) --}}
    <section class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="measure-title">
        <h2 id="measure-title" class="font-semibold">{{ __('diagnosis.published_home.measure_title') }}</h2>
        <p class="text-grey-700">{{ __('diagnosis.published_home.measure_text') }}</p>
        @if (filled(config('megis.booking_url')))
            <x-ui.button tone="outline" class="self-start" :href="config('megis.booking_url')" target="_blank" rel="noopener">{{ __('diagnosis.published_home.measure_action') }}</x-ui.button>
        @endif
    </section>

    @foreach ($otherRoles as $peerMain)
        <a href="{{ route('client.diagnosis.show', [$organization, $peerMain->diagnosis]) }}" class="text-sm font-semibold">
            {{ __('diagnosis.peer_link', ['subject' => $peerMain->subjectName()]) }}
        </a>
    @endforeach
</div>
