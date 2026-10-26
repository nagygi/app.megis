{{-- R0 · A válasz (27 · 27.2, prototype R0): the two rings, the ladder, the breakdown; Brand and Képesség are coming. --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@php
    $ring = $report['ring'];
    $meta = $report['meta'];
    $hasSummary = ! $meta['snapshot'] && ! ($meta['standalone'] ?? false);
    $firstChapter = array_key_first($report['chapters']);
@endphp
<x-layouts::client
    :title="__('report.pages.answer')"
    color="light-yellow"
    symbol="growth-rings"
    :label="__('report.pages.answer')"
    :subtitle="ReportText::copy('question.answer', $meta)"
    :note="__('report.meta', ['subject' => $meta['subject_name'], 'product' => $meta['product'], 'date' => \Illuminate\Support\Carbon::parse($meta['published_on'])->format('m.d.')])"
    :dots="$dots"
    :back="$homeUrl"
    :back-label="__('diagnosis.home_back')"
>
    <div class="flex max-w-2xl flex-col gap-5">
        <div class="flex items-center gap-4">
            <x-report.rings :percent="$ring['percent']" />
            <div class="flex min-w-0 flex-1 flex-col gap-3">
                <x-report.ring-legend
                    :percent="$ring['percent']"
                    :label="__('report.ring.label')"
                    :points="__('report.ring.points', ['points' => $ring['points'], 'max' => $ring['max'], 'count' => $ring['measuring_points']])"
                    :href="$firstChapter === null ? null : $url('chapter', [ReportService::SLUGS[$firstChapter]])"
                />
                <x-report.ring-legend tone="yellow" :label="__('report.brand_ring')" soon />
            </div>
        </div>

        @unless ($preview)
            <a href="{{ route('client.wisdom.show', [$organization, 'szazalek-es-pontok']) }}" class="self-start text-sm text-charcoal underline underline-offset-3">{{ __('report.ring.what') }}</a>
        @endunless

        <x-report.ladder :value="$ring['measuring_points'] > 0 ? $ring['points'] / $ring['measuring_points'] : null" />

        <x-report.group
            :label="__('report.groups.internal', ['count' => $ring['measuring_points'], 'points' => $ring['points'], 'max' => $ring['max']])"
            :href="$hasSummary ? $url('internal') : null"
            :link-label="$hasSummary ? __('report.groups.summary_link') : null"
        >
            @foreach ($report['chapters'] as $key => $chapter)
                <x-report.row
                    :href="$url('chapter', [ReportService::SLUGS[$key]])"
                    :title="ReportText::groupTitle($key, count($chapter['rows']))"
                    :value="$chapter['average']"
                    :number="$chapter['points']['points'].' / '.$chapter['points']['max']"
                />
            @endforeach
        </x-report.group>

        <x-report.group :label="__('report.groups.brand_soon')" soon>
            @foreach (__('report.groups.brand_rows') as $title)
                <x-report.row :title="$title" soon :pill="false" />
            @endforeach
        </x-report.group>

        <x-report.capability-card />

        <x-report.footer :status="$nav['status']" :next="$nav['next']" :next-label="$nav['nextLabel']" />
    </div>
</x-layouts::client>
