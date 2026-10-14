{{-- R1 · Vezetői összefoglaló: four blocks (27 · 27.3). --}}
@php
    $gap = $report['biggest_gap'];
    $brandRows = collect($report['chapters']['brand']['rows'] ?? []);
    $recommendation = $report['texts']['recommendation'] ?? null;
@endphp
<x-layouts::client :title="__('report.title')" color="light-yellow" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$url('show')" :back-label="__('report.ring.label')">
    <div class="flex max-w-2xl flex-col gap-4">
        <h2 class="text-2xl font-semibold">{{ __('report.question.summary') }}</h2>

        @if ($gap)
            <a href="{{ $url('item', [\App\Services\Report\ReportService::SLUGS[$gap['chapter']], $gap['id']]) }}" class="flex flex-col gap-2 rounded-card bg-charcoal p-5 text-cream no-underline on-dark">
                <span class="text-xs font-semibold uppercase tracking-wide text-coral-light">{{ __('report.biggest_gap') }}</span>
                <span class="text-xl font-extrabold uppercase">{{ $gap['label'] }}</span>
                @if ($sentence = \App\Services\Report\ReportText::gap($gap, $report['meta']))
                    <span>{{ $sentence }}</span>
                @endif
            </a>
        @endif

        @if (isset($report['chapters']['brand']))
            <a href="{{ $url('chapter', ['markad-alapja']) }}" class="flex flex-col gap-2 rounded-card bg-yellow p-5 text-charcoal no-underline">
                <span class="text-xs font-semibold uppercase tracking-wide">{{ __('report.chapters.brand') }}</span>
                @if (filled($report['texts']['summary_brand'] ?? null))
                    <span>{{ $report['texts']['summary_brand'] }}</span>
                @endif
                <span class="text-sm font-semibold">PHI {{ \App\Services\Report\ReportText::decimal($report['chapters']['brand']['average']) }}</span>
            </a>
        @endif

        <a href="{{ $url('internal') }}" class="flex flex-col gap-3 rounded-card bg-green p-5 text-cream no-underline on-dark">
            <span class="text-xs font-semibold uppercase tracking-wide">{{ __('report.strength_and_crack') }}</span>
            @foreach (['strength', 'crack'] as $kind)
                @if ($report[$kind])
                    <span>
                        <span class="block text-xs uppercase opacity-90">{{ \App\Services\Report\ReportText::claimKind($kind, $report['meta']) }} · {{ $report[$kind]['row'] }}</span>
                        <span class="block">{{ $report[$kind]['text'] }}</span>
                    </span>
                @endif
            @endforeach
        </a>

        @if ($recommendation)
            <section class="flex flex-col gap-1 rounded-card bg-yellow-pale p-5" aria-labelledby="recommendation-title">
                <h3 id="recommendation-title" class="text-xs font-semibold uppercase tracking-wide">{{ __('report.where_to_start') }}</h3>
                <p>{{ $recommendation['text'] }}</p>
                @if ($recommendation['row'])
                    <p class="text-sm font-semibold">{{ __('report.recommendation_point', ['row' => $recommendation['row'], 'from' => $recommendation['from'] ?? '–', 'to' => $recommendation['to'] ?? '–']) }}</p>
                @endif
            </section>
        @endif

        @include('client.report.partials.footer', ['current' => null])
    </div>
</x-layouts::client>
