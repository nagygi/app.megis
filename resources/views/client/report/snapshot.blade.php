{{-- S1 · Snapshot · Belső on one page (27 · 27.10, prototype S1): the ring, the ladder, the breakdown, a strength, a breakpoint, one step. --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@php
    $ring = $report['ring'];
    $meta = $report['meta'];
    $recommendation = $report['texts']['recommendation'] ?? null;
    $window = $report['appendix']['window'];
    $measured = collect($report['processes']['measured'])->pluck('name');
    $processRows = collect($report['chapters']['processes']['rows'] ?? []);
@endphp
<x-layouts::client
    :title="__('report.pages.snapshot')"
    color="green"
    symbol="growth-rings"
    :label="__('report.pages.snapshot')"
    :subtitle="ReportText::copy('question.answer', $meta)"
    :note="$measured->isEmpty() ? null : __('report.snapshot_page.meta', ['processes' => $measured->implode(' és '), 'from' => $window['from'] ? \Illuminate\Support\Carbon::parse($window['from'])->format('m.d.') : '–', 'to' => \Illuminate\Support\Carbon::parse($window['to'])->format('m.d.')])"
    :back="$homeUrl"
    :back-label="__('diagnosis.home_back')"
>
    <div class="flex max-w-2xl flex-col gap-5">
        <div class="flex items-center gap-4">
            <x-report.rings :percent="$ring['percent']" :brand="false" />
            <x-report.ring-legend
                class="min-w-0 flex-1"
                :percent="$ring['percent']"
                :label="__('report.ring.label')"
                :points="__('report.ring.points', ['points' => $ring['points'], 'max' => $ring['max'], 'count' => $ring['measuring_points']])"
            />
        </div>

        @unless ($preview)
            <a href="{{ route('client.wisdom.show', [$organization, 'szazalek-es-pontok']) }}" class="self-start text-sm text-charcoal underline underline-offset-3">{{ __('report.ring.what') }}</a>
        @endunless

        <x-report.ladder :value="$ring['measuring_points'] > 0 ? $ring['points'] / $ring['measuring_points'] : null" />

        <div class="flex flex-col">
            @foreach (['values', 'brand'] as $key)
                @isset($report['chapters'][$key])
                    @php($chapter = $report['chapters'][$key])
                    <x-report.row
                        :href="$url('chapter', [ReportService::SLUGS[$key]])"
                        :title="ReportText::groupTitle($key, count($chapter['rows']))"
                        :value="$chapter['average']"
                        :number="$chapter['points']['points'].' / '.$chapter['points']['max']"
                    />
                @endisset
            @endforeach
            @foreach ($processRows as $row)
                <x-report.row
                    :href="$url('item', [ReportService::SLUGS['processes'], $row['id']])"
                    :title="$row['label']"
                    :value="$row['score']"
                    :number="($row['score_whole'] ?? '–').' / 6'"
                />
            @endforeach
        </div>

        @foreach (['strength', 'crack'] as $kind)
            @if ($report[$kind])
                <div class="flex flex-col gap-1">
                    <p class="text-xs font-semibold tracking-[.08em] text-green uppercase">{{ ReportText::claimKind($kind, $meta) }} · {{ __('report.nouns.'.$report[$kind]['chapter']) }} · {{ $report[$kind]['row'] }}</p>
                    <p class="text-lg leading-snug font-semibold">{{ $report[$kind]['text'] }}</p>
                    <p class="text-xs text-grey-700"><span class="font-semibold text-green">{{ __('report.claim_marker_short') }}</span> · {{ __('report.claim_reviewed') }}</p>
                    @if ($report[$kind]['quote'])
                        <figure class="mt-1 rounded-[18px] bg-grey-100 px-3.5 py-3 text-sm">„{{ $report[$kind]['quote'] }}”</figure>
                    @endif
                </div>
            @endif
        @endforeach

        @if ($recommendation)
            <section class="flex flex-col gap-1 rounded-[18px] bg-grey-100 px-3.5 py-3" aria-labelledby="snapshot-recommendation">
                <h2 id="snapshot-recommendation" class="flex items-center gap-1.5 text-xs font-semibold tracking-[.07em] text-green uppercase"><x-megis.symbol name="spiral" class="size-4 text-green" />{{ __('report.recommendation') }}</h2>
                <div class="prose-megis leading-snug font-semibold">{!! \Illuminate\Support\Str::markdown($recommendation['text'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                @if ($recommendation['row'])
                    <p class="text-sm text-grey-700">{{ __('report.recommendation_point', ['row' => $recommendation['row'], 'from' => $recommendation['from'] ?? '–', 'to' => $recommendation['to'] ?? '–']) }}</p>
                @endif
            </section>
        @endif

        @isset($report['chapters']['brand'])
            <div class="flex flex-col">
                <x-report.row
                    :href="$url('chapter', ['markad-alapja'])"
                    :title="__('report.snapshot.brand_link')"
                    :meta="__('report.snapshot_page.brand_row').' · '.$report['chapters']['brand']['points']['points'].' / '.$report['chapters']['brand']['points']['max']"
                    symbol="root"
                    :bar="false"
                />
            </div>
        @endisset

        @unless ($preview)
            <livewire:client.report.feedback :main="$main" chapter="snapshot" key="feedback-snapshot" />
        @endunless

        <x-ui.card as="section" :bordered="false" padding="sm" class="flex flex-col gap-1.5 border-2 border-coral" aria-labelledby="snapshot-coach">
            <h2 id="snapshot-coach" class="flex items-center gap-1.5 text-xs font-semibold tracking-[.07em] uppercase"><x-megis.symbol name="spiral" class="size-4 text-coral" />{{ __('report.coach.name') }} <x-report.soon /></h2>
            <p class="text-sm leading-relaxed text-grey-700">{{ __('report.snapshot_page.coach') }}</p>
        </x-ui.card>

        <div class="flex flex-col gap-3 border-t border-grey-250 pt-3.5">
            @if (filled(config('megis.booking_url')))
                <x-ui.button size="sm" icon-right="arrow-right" class="self-start" :href="config('megis.booking_url')" target="_blank" rel="noopener">
                    {{ __('report.snapshot.deeper_action') }}
                    <span class="sr-only">({{ __('ui.new_tab') }})</span>
                </x-ui.button>
            @endif
            <div class="on-dark flex items-center gap-3 rounded-card bg-charcoal px-4 py-4 text-cream">
                <span class="min-w-0 flex-1">
                    <span class="block text-xs font-semibold tracking-[.07em] text-yellow uppercase">{{ __('report.snapshot_page.deeper') }}</span>
                    <span class="mt-1 block font-semibold">{{ __('report.snapshot_page.outside') }}</span>
                    <span class="mt-0.5 block text-xs text-grey-400">{{ __('report.snapshot_page.outside_meta') }}</span>
                </span>
                <x-report.soon dark />
            </div>
        </div>
    </div>
</x-layouts::client>
