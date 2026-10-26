{{-- R2·b · R3·b · R4·b · R5·b: "Miért {p} a {tétel}?", frozen to the diagnosis' date (27 · 27.5–27.8, prototype R2·b). --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@php
    $key = $chapter['key'];
    $meta = $report['meta'];
    $chapterUrl = $url('chapter', [ReportService::SLUGS[$key]]);
    $symbol = match ($key) {
        'brand' => \App\Enums\TreeElement::from($item['element'])->symbol(),
        'processes' => 'soil',
        'areas' => 'growth-rings',
        default => 'root',
    };
    $whole = $item['score_whole'] ?? null;
    $experienceLine = match (true) {
        $meta['has_peers'] => __('report.experience_line', ['leader' => ReportText::decimal($item['leader']), 'peers' => ReportText::decimal($item['peers']), 'count' => $item['peer_count']]),
        $item['situations'] !== null => __('report.experience_self', ['leader' => ReportText::decimal($item['leader']), 'situations' => ReportText::decimal($item['situations'])]),
        default => null,
    };
@endphp
<x-layouts::client
    :title="$item['label']"
    :color="$key === 'brand' ? 'light-yellow' : 'green'"
    :symbol="$symbol"
    :label="$item['label']"
    :subtitle="__('report.question.item', ['score' => ReportText::score($item), 'label' => $item['label']])"
    :badge="__('report.diagnosis_view')"
    :note="__('report.item_meta', ['date' => \Illuminate\Support\Carbon::parse($meta['published_on'])->format('m.d.'), 'area' => __('report.item_area.'.$key), 'chapter' => __('report.item_chapter.'.$key)])"
    :back="$chapterUrl"
    :back-label="__('report.chapters.'.$key)"
>
    <div class="flex max-w-2xl flex-col gap-5">
        @unless ($meta['snapshot'] || ($meta['standalone'] ?? false))
            @include('client.report.partials.switch')
        @endunless

        <section class="flex items-center gap-3.5" aria-label="{{ __('report.phi') }}">
            <p class="flex shrink-0 flex-col items-center">
                <span class="text-xs font-semibold tracking-[.07em] text-grey-700">{{ __('report.phi') }}</span>
                <span @class(['text-5xl leading-none font-extrabold tabular-nums', 'text-coral-ink' => $whole !== null && $whole <= 2])>{{ ReportText::score($item) }}</span>
            </p>
            <x-report.ladder class="min-w-0 flex-1" :value="$item['score']" marker="charcoal" />
        </section>

        @if (in_array($key, ['values', 'brand', 'processes'], true))
            @if ($item['experience'] !== null && $meta['shows_experience'])
                <div class="flex flex-col border-t border-grey-200">
                    <x-report.row :title="__('report.experience')" :meta="$experienceLine" :bar="false" :number="$item['experience_whole']" />
                </div>
            @endif
            @if ($sentence = ReportText::gap($item, $meta))
                <p @class(['font-semibold', 'text-coral-ink' => $meta['shows_experience'] && ($item['gap'] ?? null) !== null, 'text-grey-700' => ! $meta['shows_experience']])>{{ $sentence }}</p>
            @endif
        @endif

        @if ($item['occasions'])
            <p class="text-sm text-grey-700">{{ __('report.occasions', ['count' => $item['occasions']['count'], 'yes' => $item['occasions']['yes'] ?? 0, 'partial' => $item['occasions']['partial'] ?? 0, 'no' => $item['occasions']['no'] ?? 0]) }}</p>
        @endif

        @if ($key === 'brand')
            <p class="flex items-center gap-2 text-xs font-semibold tracking-[.08em] text-green uppercase">{{ __('report.tree.outside') }} <x-report.soon /></p>
        @endif

        @if ($item['definition'])
            <section class="flex flex-col gap-1" aria-labelledby="definition-title">
                <h2 id="definition-title" class="text-xs font-semibold tracking-[.08em] text-green uppercase">{{ ReportText::copy('definition', $meta) }}</h2>
                @if (filled($item['definition']['meaning']))
                    <p class="text-sm leading-relaxed">{{ $item['definition']['meaning'] }}</p>
                @endif
                <dl class="grid gap-x-2 gap-y-0.5 text-sm sm:grid-cols-[5rem_1fr]">
                    <dt class="font-semibold text-coral-ink">{{ __('report.definition_low') }}</dt>
                    <dd>{{ $item['definition']['low'] ?: '–' }}</dd>
                    <dt class="font-semibold text-green">{{ __('report.definition_high') }}</dt>
                    <dd>{{ $item['definition']['high'] ?: '–' }}</dd>
                </dl>
            </section>
        @endif

        @if ($item['what_we_look_at'])
            <section class="flex flex-col gap-1" aria-labelledby="look-title">
                <h2 id="look-title" class="text-xs font-semibold tracking-[.08em] text-green uppercase">{{ __('report.what_we_look_at') }}</h2>
                <p class="text-sm leading-relaxed">{{ $item['what_we_look_at'] }}</p>
            </section>
        @endif

        @foreach ($item['claims'] as $claim)
            @include('client.report.partials.claim', ['claim' => $claim, 'claimRow' => null, 'withQuote' => false])
        @endforeach

        <section class="flex flex-col gap-2" aria-labelledby="evidence-title">
            <h2 id="evidence-title" class="text-xs font-semibold tracking-[.08em] text-green uppercase">{{ trans_choice('report.evidence', count($item['traces']), ['count' => count($item['traces'])]) }}</h2>
            @foreach ($item['traces'] as $trace)
                @include('client.report.partials.quote', ['trace' => $trace])
            @endforeach
        </section>

        <div class="flex flex-col border-t border-grey-200">
            <x-report.row :title="__('report.coach.why')" :meta="__('report.coach.from_evidence')" symbol="spiral" soon />
        </div>

        <x-report.footer :status="$nav['status']" :next="$nav['next']" :next-label="$nav['nextLabel']" />
    </div>
</x-layouts::client>
