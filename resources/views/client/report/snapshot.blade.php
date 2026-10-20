{{-- S1 · the internal snapshot on one page (27 · 27.10). --}}
@use('App\Services\Report\ReportText')
@php
    $ring = $report['ring'];
    $recommendation = $report['texts']['recommendation'] ?? null;
@endphp
<x-layouts::client :title="__('report.title')" color="light-yellow" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$homeUrl" :back-label="__('diagnosis.home_back')">
    <div class="flex max-w-2xl flex-col gap-6">
        <h2 class="text-2xl font-semibold">{{ __('report.question.answer') }}</h2>

        @if ($report['processes']['measured'] !== [])
            <p class="text-grey-700">
                {{ __('report.snapshot.processes') }}: {{ collect($report['processes']['measured'])->pluck('name')->implode(' · ') }}
                · {{ __('report.snapshot.window', ['from' => $report['appendix']['window']['from'] ? \Illuminate\Support\Carbon::parse($report['appendix']['window']['from'])->format('m.d.') : '–', 'to' => \Illuminate\Support\Carbon::parse($report['appendix']['window']['to'])->format('m.d.')]) }}
            </p>
        @endif

        <section class="flex flex-col items-center gap-3 rounded-card bg-white p-6 text-center sm:flex-row sm:text-left" aria-label="{{ __('report.ring.label') }}">
            <x-report.ring :percent="$ring['percent']" />
            <div class="flex flex-col gap-1">
                <p class="text-lg font-semibold">{{ __('report.ring.label') }}</p>
                <p class="text-grey-700">{{ __('report.ring.points', ['points' => $ring['points'], 'max' => $ring['max'], 'count' => $ring['measuring_points']]) }}</p>
            </div>
        </section>
        <x-report.scale :value="$ring['step']" />

        @foreach (['values', 'brand', 'processes'] as $key)
            @continue(! isset($report['chapters'][$key]))
            <section class="flex flex-col gap-2" aria-labelledby="snapshot-{{ $key }}">
                <h3 id="snapshot-{{ $key }}" class="text-lg font-semibold">{{ __('report.chapters.'.$key) }} · {{ __('report.block_points', $report['chapters'][$key]['points']) }}</h3>
                <ul class="flex flex-col gap-1">
                    @foreach ($report['chapters'][$key]['rows'] as $row)
                        <li class="flex flex-col gap-0.5 rounded-field bg-white px-4 py-2">
                            <span class="flex justify-between gap-3"><span>{{ $row['label'] }}</span><span class="font-semibold tabular-nums">{{ ReportText::score($row) }}</span></span>
                            @if ($sentence = ReportText::gap($row, $report['meta']))
                                <span class="text-sm text-grey-700">{{ $sentence }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        @foreach (['strength', 'crack'] as $kind)
            @if ($report[$kind])
                <section class="flex flex-col gap-2" aria-label="{{ ReportText::claimKind($kind, $report['meta']) }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-grey-700">{{ ReportText::claimKind($kind, $report['meta']) }} · {{ $report[$kind]['row'] }}</p>
                    <p>{{ $report[$kind]['text'] }}</p>
                    @if ($report[$kind]['quote'])
                        <figure class="rounded-card bg-grey-100 p-4">„{{ $report[$kind]['quote'] }}”</figure>
                    @endif
                </section>
            @endif
        @endforeach

        @if ($recommendation)
            <section class="flex flex-col gap-1 rounded-card bg-yellow-pale p-5" aria-labelledby="snapshot-recommendation">
                <h3 id="snapshot-recommendation" class="font-semibold">{{ __('report.recommendation') }}</h3>
                <p>{{ $recommendation['text'] }}</p>
                @if ($recommendation['row'])
                    <p class="text-sm font-semibold">{{ __('report.recommendation_point', ['row' => $recommendation['row'], 'from' => $recommendation['from'] ?? '–', 'to' => $recommendation['to'] ?? '–']) }}</p>
                @endif
            </section>
        @endif

        @if (isset($report['chapters']['brand']))
            <a href="{{ $url('chapter', ['markad-alapja']) }}" class="font-semibold">{{ __('report.snapshot.brand_link') }}</a>
        @endif

        @if (filled(config('megis.booking_url')))
            <a href="{{ config('megis.booking_url') }}" target="_blank" rel="noopener" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-charcoal no-underline hover:bg-paper">
                <span>{{ __('report.snapshot.deeper') }}</span>
                <span class="font-semibold">{{ __('report.snapshot.deeper_action') }}</span>
            </a>
        @endif

        @unless ($preview)
            <livewire:client.report.feedback :main="$main" chapter="snapshot" key="feedback-snapshot" />
        @endunless
    </div>
</x-layouts::client>
