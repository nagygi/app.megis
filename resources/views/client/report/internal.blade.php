{{-- R1·b · Belső működés: every inner measuring point on one page (27 · 27.4). --}}
<x-layouts::client :title="__('report.question.internal')" color="green" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$url('summary')" :back-label="__('report.question.summary')">
    <div class="flex max-w-2xl flex-col gap-6">
        @include('client.report.partials.switch')

        @foreach ($report['chapters'] as $key => $chapter)
            @php($rows = $mode === 'csapat' ? collect($chapter['rows']) : ($mode === 'egyen' ? $individual : $organizationView)->where('kind', \App\Services\Report\ReportBuilder::CHAPTERS[$key]->value)->sortByDesc('score'))
            @continue($rows->isEmpty())
            <section class="flex flex-col gap-2" aria-labelledby="internal-{{ $key }}">
                <h2 id="internal-{{ $key }}" class="text-lg font-semibold">
                    <a href="{{ $url('chapter', [\App\Services\Report\ReportService::SLUGS[$key]]) }}" class="inline-flex items-center gap-1 text-charcoal no-underline hover:underline">{{ __('report.chapters.'.$key) }} <x-megis.icon name="chevron-right" /></a>
                </h2>
                <ul class="flex flex-col gap-1">
                    @foreach ($rows as $row)
                        <li class="flex items-center justify-between gap-3 rounded-field border border-grey-250 bg-white px-4 py-2">
                            <span>
                                {{ $row['label'] }}
                                @if ($row['is_biggest_gap'] ?? false)
                                    <x-ui.badge tone="coral" class="ml-1">{{ __('report.biggest_gap') }}</x-ui.badge>
                                @elseif ($row['has_crack'] ?? false)
                                    <x-ui.badge class="ml-1">{{ $report['meta']['snapshot'] ? __('report.breakpoint_marker') : __('report.crack_marker') }}</x-ui.badge>
                                @endif
                            </span>
                            <span class="font-semibold tabular-nums">{{ isset($row['score_whole']) ? \App\Services\Report\ReportText::score($row) : (($row['score'] ?? null) === null ? '–' : \App\Services\Report\ReportText::decimal($row['score'])) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        @foreach (['strength', 'crack'] as $kind)
            @if ($report[$kind])
                <section class="flex flex-col gap-2" aria-label="{{ \App\Services\Report\ReportText::claimKind($kind, $report['meta']) }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-grey-700">{{ \App\Services\Report\ReportText::claimKind($kind, $report['meta']) }} · {{ $report[$kind]['row'] }}</p>
                    <p>{{ $report[$kind]['text'] }}</p>
                    @if ($report[$kind]['quote'])
                        <figure class="rounded-card bg-grey-100 p-4">„{{ $report[$kind]['quote'] }}”</figure>
                    @endif
                </section>
            @endif
        @endforeach

        <x-ui.button tone="outline" size="sm" icon-right="chevron-right" class="self-start" :href="$url('compare')">{{ __('report.compare_link') }}</x-ui.button>

        @include('client.report.partials.footer', ['current' => null])
    </div>
</x-layouts::client>
