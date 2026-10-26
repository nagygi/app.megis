{{-- R1 · Vezetői összefoglaló (27 · 27.3, prototype R1): five coloured blocks, each opening its page; Brand riport and Képesség are coming. --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@php
    $meta = $report['meta'];
    $gap = $report['biggest_gap'];
    $recommendation = $report['texts']['recommendation'] ?? null;
@endphp
<x-layouts::client
    :title="__('report.pages.summary')"
    color="light-yellow"
    symbol="fire"
    :label="__('report.pages.summary')"
    :subtitle="__('report.summary_page.question')"
    :dots="$dots"
    :back="$url('show')"
    :back-label="__('report.pages.answer')"
>
    <div class="flex max-w-2xl flex-col gap-3.5">
        @if ($gap)
            <a href="{{ $url('item', [ReportService::SLUGS[$gap['chapter']], $gap['id']]) }}" class="press on-dark block rounded-card bg-charcoal p-4 text-cream no-underline">
                <span class="block text-xl leading-tight font-extrabold uppercase">{{ __('report.summary_page.biggest_gap', ['label' => $gap['label']]) }}</span>
                @if ($sentence = ReportText::gap($gap, $meta))
                    <span class="mt-1.5 block text-sm text-grey-200">{{ $sentence }}</span>
                @endif
            </a>
        @elseif (! $meta['shows_experience'])
            {{-- Below the threshold there is no gap: no row is named (Extra inputok 1). --}}
            <div class="on-dark rounded-card bg-charcoal p-4 text-cream">
                <span class="block text-xs font-semibold tracking-wide text-coral-light uppercase">{{ __('report.biggest_gap') }}</span>
                <span class="mt-1.5 block">{{ ReportText::missingPeers($meta) }}</span>
            </div>
        @endif

        @if (isset($report['chapters']['brand']))
            <a href="{{ $url('chapter', ['markad-alapja']) }}" class="press flex flex-col gap-2 rounded-card bg-yellow p-4 text-charcoal no-underline">
                <span class="flex items-center gap-2">
                    <x-megis.symbol name="tree-of-life" class="size-5" />
                    <span class="flex-1 text-xs font-semibold tracking-[.07em] uppercase">{{ __('report.summary_page.brand', ['inside' => ReportText::decimal($report['chapters']['brand']['average'])]) }}</span>
                    <x-megis.icon name="chevron-right" />
                </span>
                @if (filled($report['texts']['summary_brand'] ?? null))
                    <span class="leading-snug font-semibold">{{ $report['texts']['summary_brand'] }}</span>
                @endif
                <span class="flex items-center gap-1.5 text-xs"><x-megis.symbol name="spiral" class="size-3.5 text-coral-ink" />{{ __('report.brand_reading') }} · {{ __('report.brand_reading_marker') }}</span>
            </a>
        @endif

        <a href="{{ $url('internal') }}" class="press on-dark flex flex-col gap-2 rounded-card bg-green p-4 text-cream no-underline">
            <span class="flex items-center justify-between text-xs font-semibold tracking-[.07em] text-yellow uppercase">
                <span>{{ __('report.summary_page.internal', ['percent' => $report['ring']['percent'] ?? '–']) }}</span>
                <x-megis.icon name="chevron-right" />
            </span>
            @foreach (['strength', 'crack'] as $kind)
                @if ($report[$kind])
                    <span class="leading-snug font-semibold">{{ ReportText::claimKind($kind, $meta) }}: {{ $report[$kind]['text'] }}</span>
                @endif
            @endforeach
            <span class="text-xs text-grey-200">{{ __('report.summary_page.internal_meta') }}</span>
        </a>

        <section class="flex flex-col gap-2 rounded-card bg-yellow-light p-4" aria-labelledby="brand-report-title">
            <span class="flex items-center justify-between gap-2">
                <h2 id="brand-report-title" class="text-xs font-semibold tracking-[.07em] uppercase">{{ __('report.brand_report.title') }}</h2>
                <x-report.soon />
            </span>
            <p class="text-sm leading-snug text-grey-700">{{ __('report.brand_report.text') }}</p>
        </section>

        <x-report.capability-card framed :overview="$capabilities" :href="$capabilities ? route('client.capabilities', $organization) : null">
            @if ($recommendation)
                <span class="font-semibold">{{ __('report.summary_page.where_to_start') }}</span> {!! \Illuminate\Support\Str::inlineMarkdown(\Illuminate\Support\Str::of($recommendation['text'])->replaceMatches('/^#+\s*/m', '')->squish(), ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
                @if ($recommendation['row'])
                    <span class="mt-1 block font-semibold">{{ __('report.recommendation_point', ['row' => $recommendation['row'], 'from' => $recommendation['from'] ?? '–', 'to' => $recommendation['to'] ?? '–']) }}</span>
                @endif
            @endif
        </x-report.capability-card>

        <p class="text-xs text-grey-700"><span class="font-semibold text-green">{{ __('report.summary_page.claims') }}</span> · {{ __('report.summary_page.claims_marker') }}</p>

        <x-report.footer :status="$nav['status']" :next="$nav['next']" :next-label="$nav['nextLabel']" />
    </div>
</x-layouts::client>
