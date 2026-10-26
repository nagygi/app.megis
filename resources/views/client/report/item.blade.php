{{-- R2·b · R3·b · R4·b · R5·b: "Miért {p} a {tétel}?" (27 · 27.5–27.8). --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@php
    $key = $chapter['key'];
    $slug = ReportService::SLUGS[$key];
    $chapterUrl = $url('chapter', [$slug]);
@endphp
<x-layouts::client :title="$item['label']" :color="$key === 'brand' ? 'light-yellow' : 'green'" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$chapterUrl" :back-label="__('report.back_to_chapter', ['chapter' => __('report.chapters.'.$key)])">
    <div class="flex max-w-2xl flex-col gap-6">
        <h2 class="text-xl font-semibold">{{ __('report.question.item', ['score' => ReportText::score($item), 'label' => $item['label']]) }}</h2>

        @unless ($report['meta']['standalone'] ?? false)
            @include('client.report.partials.switch')
        @endunless

        <x-ui.card as="section" class="flex flex-col gap-2" :aria-label="__('report.phi')">
            <p class="text-3xl font-extrabold tabular-nums">{{ ReportText::score($item) }}</p>
            <x-report.scale :value="$item['score']" />
            @if (in_array($key, ['values', 'brand'], true) || $key === 'processes')
                @if ($report['meta']['shows_experience'] && ($item['leader'] !== null || $item['peers'] !== null))
                    <p class="text-sm">{{ $report['meta']['has_peers']
                        ? __('report.experience_line', ['leader' => ReportText::decimal($item['leader']), 'peers' => ReportText::decimal($item['peers']), 'count' => $item['peer_count']])
                        : __('report.experience_self', ['leader' => ReportText::decimal($item['leader']), 'situations' => ReportText::decimal($item['situations'])]) }}</p>
                @endif
                @if ($sentence = ReportText::gap($item, $report['meta']))
                    <p @class(['text-grey-700' => ! $report['meta']['shows_experience']])>{{ $sentence }}</p>
                @endif
            @endif
            @if ($item['occasions'])
                <p class="text-sm text-grey-700">{{ __('report.occasions', ['count' => $item['occasions']['count'], 'yes' => $item['occasions']['yes'] ?? 0, 'partial' => $item['occasions']['partial'] ?? 0, 'no' => $item['occasions']['no'] ?? 0]) }}</p>
            @endif
        </x-ui.card>

        @if ($item['definition'])
            <section class="flex flex-col gap-2" aria-labelledby="definition-title">
                <h3 id="definition-title" class="font-semibold">{{ ReportText::copy('definition', $report['meta']) }}</h3>
                @if (filled($item['definition']['meaning']))
                    <p>{{ $item['definition']['meaning'] }}</p>
                @endif
                <x-ui.card as="dl" padding="sm" class="grid gap-1 text-sm sm:grid-cols-[6rem_1fr]" :aria-label="ReportText::copy('definition', $report['meta'])">
                    <dt class="font-semibold text-coral-ink">{{ __('report.definition_low') }}</dt>
                    <dd>{{ $item['definition']['low'] ?: '–' }}</dd>
                    <dt class="font-semibold text-green">{{ __('report.definition_high') }}</dt>
                    <dd>{{ $item['definition']['high'] ?: '–' }}</dd>
                </x-ui.card>
            </section>
        @endif

        @if ($item['what_we_look_at'])
            <section class="flex flex-col gap-1" aria-labelledby="look-title">
                <h3 id="look-title" class="font-semibold">{{ __('report.what_we_look_at') }}</h3>
                <p>{{ $item['what_we_look_at'] }}</p>
            </section>
        @endif

        @foreach ($item['claims'] as $claim)
            @include('client.report.partials.claim', ['claim' => $claim, 'withQuote' => false])
        @endforeach

        <section class="flex flex-col gap-2" aria-labelledby="evidence-title">
            <h3 id="evidence-title" class="font-semibold">{{ trans_choice('report.evidence', count($item['traces']), ['count' => count($item['traces'])]) }}</h3>
            @foreach ($item['traces'] as $trace)
                @include('client.report.partials.quote', ['trace' => $trace])
            @endforeach
        </section>

        <x-ui.page-nav
            :label="__('report.chapters.'.$key)"
            :back="$chapterUrl"
            :back-label="__('report.back_to_chapter', ['chapter' => __('report.chapters.'.$key)])"
            :next="$next ? $url('item', [$slug, $next['id']]) : null"
            :next-label="$next ? __('report.next_item', ['label' => $next['label']]) : null"
        />
    </div>
</x-layouts::client>
