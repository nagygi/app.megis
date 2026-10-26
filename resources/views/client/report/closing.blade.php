{{-- R9 · A diagnózisotok (27 · 27.11, prototype R9): one sentence, a quiet summary, the mégis' recommendation with the one action, booking. --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@php
    $meta = $report['meta'];
    $recommendation = $report['texts']['recommendation'] ?? null;
    $lastChapter = array_key_last($report['chapters']);
    $gap = $report['biggest_gap'];
@endphp
<x-layouts::client
    :title="ReportText::copy('pages.closing', $meta)"
    color="light-yellow"
    symbol="growth-rings"
    :label="ReportText::copy('pages.closing', $meta)"
    :subtitle="__('report.meta', ['subject' => $meta['subject_name'], 'product' => $meta['product'], 'date' => \Illuminate\Support\Carbon::parse($meta['published_on'])->format('m.d.')])"
    :back="$lastChapter === null ? $url('show') : $url('chapter', [ReportService::SLUGS[$lastChapter]])"
    :back-label="$lastChapter === null ? __('report.pages.answer') : __('report.chapters.'.$lastChapter)"
>
    <div class="flex max-w-2xl flex-col gap-6">
        <div class="flex flex-col gap-2.5">
            <h2 class="text-2xl leading-tight font-extrabold uppercase [text-wrap:balance]">{{ ReportText::copy('closing.title', $meta) }}</h2>
            <p class="text-sm leading-relaxed">{{ ReportText::copy('closing.lead', $meta) }}</p>
        </div>

        <dl class="flex flex-col">
            <div class="flex items-baseline justify-between gap-3 border-b border-grey-200 py-2.5">
                <dt class="text-sm text-grey-700">{{ __('report.closing_page.internal_brand') }}</dt>
                <dd class="flex items-center gap-1.5 text-right font-semibold">{{ $report['ring']['percent'] === null ? '–' : $report['ring']['percent'].'%' }} · <x-report.soon /></dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 border-b border-grey-200 py-2.5">
                <dt class="text-sm text-grey-700">{{ __('report.biggest_gap') }}</dt>
                <dd @class(['text-right font-semibold', 'text-coral-ink' => $gap !== null])>{{ $gap['label'] ?? '–' }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 border-b border-grey-200 py-2.5">
                <dt class="text-sm text-grey-700">{{ ReportText::copy('capability.start_with', $meta) }}</dt>
                <dd><x-report.soon /></dd>
            </div>
        </dl>

        <section class="relative flex flex-col gap-3.5 overflow-hidden rounded-[28px] bg-yellow px-4.5 py-5" aria-labelledby="closing-recommendation">
            <x-megis.symbol name="trunk" class="absolute -top-3.5 -right-4.5 size-28 text-yellow-dark" />
            <h3 id="closing-recommendation" class="relative text-xs font-semibold tracking-[.07em] uppercase">{{ __('report.closing_page.recommendation') }}</h3>
            @if ($recommendation)
                {{-- Written in the md editor: subheadings, bold, italic, links (04 · 4.6, 20 · 20.4). --}}
                <div class="prose-megis relative rounded-[18px] bg-cream px-3.5 py-3">{!! \Illuminate\Support\Str::markdown($recommendation['text'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                @if ($recommendation['row'])
                    <p class="relative text-sm font-semibold">{{ __('report.recommendation_point', ['row' => $recommendation['row'], 'from' => $recommendation['from'] ?? '–', 'to' => $recommendation['to'] ?? '–']) }}</p>
                @endif
            @endif
            <p class="relative text-sm leading-relaxed">{{ ReportText::copy('closing.hour', $meta) }}</p>
            @if (filled(config('megis.booking_url')))
                <x-ui.button size="lg" icon-right="arrow-right" class="relative w-full" :href="config('megis.booking_url')" target="_blank" rel="noopener">
                    {{ __('report.closing.book') }}
                    <span class="sr-only">({{ __('ui.new_tab') }})</span>
                </x-ui.button>
            @endif
        </section>

        <div class="flex flex-col">
            <x-report.row :title="__('report.dots.bridge')" :meta="__('report.closing_page.bridge_meta')" symbol="trunk" soon />
        </div>

        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-grey-700">
            @unless ($preview)
                <a href="{{ $url('pdf') }}" class="inline-flex items-center gap-1.5 text-grey-700 no-underline hover:underline"><x-megis.icon name="document" />{{ __('report.closing.pdf') }}</a>
            @endunless
            <span class="inline-flex items-center gap-1.5">{{ __('report.closing_page.share') }} <x-report.soon /></span>
            <a href="{{ $url('appendix') }}" class="inline-flex items-center gap-1.5 text-grey-700 no-underline hover:underline"><x-megis.icon name="document" />{{ __('report.appendix_link') }}</a>
        </div>
    </div>
</x-layouts::client>
