{{-- The chapter stepper at the bottom of a page: "{k} / {n} · {fejezet}" and the next one (27 · 27.1). --}}
@use('App\Services\Report\ReportText')
@php
    $position = $current === null ? null : array_search($current, $chapterKeys, true);
    $nextKey = $position === null ? ($chapterKeys[0] ?? null) : ($chapterKeys[$position + 1] ?? null);
    $nextUrl = $nextKey === null
        ? $url('closing')
        : $url('chapter', [\App\Services\Report\ReportService::SLUGS[$nextKey]]);
@endphp

<x-ui.page-nav
    :label="ReportText::copy('title', $report['meta'])"
    :position="$position === null ? null : __('report.stepper', ['current' => $position + 1, 'total' => count($chapterKeys), 'chapter' => __('report.chapters.'.$current)])"
    :next="$nextUrl"
    :next-label="__('report.next', ['chapter' => $nextKey === null ? ReportText::copy('closing.title', $report['meta']) : __('report.chapters.'.$nextKey)])"
/>
