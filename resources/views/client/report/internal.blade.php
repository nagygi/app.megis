{{-- R1·b · Belső működés: every inner measuring point on one page, the gate of chapters 2–5 (27 · 27.4, prototype R1·b). --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@php
    $ring = $report['ring'];
    $meta = $report['meta'];
@endphp
<x-layouts::client
    :title="__('report.pages.internal')"
    color="green"
    symbol="root"
    :label="__('report.pages.internal')"
    :subtitle="__('report.internal_page.question')"
    :note="__('report.internal_page.meta', ['percent' => $ring['percent'] ?? '–', 'points' => $ring['points'], 'max' => $ring['max'], 'count' => $ring['measuring_points']])"
    :back="$url('summary')"
    :back-label="__('report.dots.summary')"
>
    <div class="flex max-w-2xl flex-col gap-5">
        @include('client.report.partials.switch')

        @foreach ($report['chapters'] as $key => $chapter)
            @php
                $rows = $mode === 'csapat' ? collect($chapter['rows']) : ($mode === 'egyen' ? $individual : $organizationView)->where('kind', \App\Services\Report\ReportBuilder::CHAPTERS[$key]->value)->sortByDesc('score')->values();
            @endphp
            @continue($rows->isEmpty())
            <x-report.group
                :label="__('report.groups.points', ['label' => ReportText::groupTitle($key, count($chapter['rows'])), 'points' => $chapter['points']['points'], 'max' => $chapter['points']['max']])"
                :href="$url('chapter', [ReportService::SLUGS[$key]])"
            >
                @foreach ($rows as $row)
                    @php
                        $whole = isset($row['score_whole']) ? $row['score_whole'] : (($row['score'] ?? null) === null ? null : (int) floor($row['score'] + 0.5));
                        $marker = match (true) {
                            $row['is_biggest_gap'] ?? false => __('report.biggest_gap_short'),
                            $row['has_crack'] ?? false => $meta['snapshot'] ? __('report.breakpoint_marker') : __('report.crack_marker'),
                            default => null,
                        };
                    @endphp
                    <x-report.row
                        :href="isset($row['id']) && $marker !== null ? $url('item', [ReportService::SLUGS[$key], $row['id']]) : $url('chapter', [ReportService::SLUGS[$key]])"
                        :title="$row['label']"
                        :meta="$marker"
                        meta-tone="coral"
                        :value="$row['score'] ?? null"
                        :number="isset($row['score_whole']) ? ReportText::score($row) : ($whole ?? '–')"
                        :low="$whole !== null && $whole <= 2"
                    />
                @endforeach
                @if ($key === 'brand')
                    <x-report.row :title="__('report.groups.tree_more')" soon />
                @endif
            </x-report.group>
        @endforeach

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

        <x-report.footer :status="$nav['status']" :next="$nav['next']" :next-label="$nav['nextLabel']" />
    </div>
</x-layouts::client>
