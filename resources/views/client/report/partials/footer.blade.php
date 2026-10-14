{{-- The chapter stepper at the bottom of a page: "{k} / {n} · {fejezet}" and the next one (27 · 27.1). --}}
@php
    $position = $current === null ? null : array_search($current, $chapterKeys, true);
    $nextKey = $position === null ? ($chapterKeys[0] ?? null) : ($chapterKeys[$position + 1] ?? null);
    $nextUrl = $nextKey === null
        ? $url('closing')
        : $url('chapter', [\App\Services\Report\ReportService::SLUGS[$nextKey]]);
@endphp

<nav class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-grey-250 pt-4 text-sm" aria-label="{{ __('report.title') }}">
    @if ($position !== null)
        <span class="text-grey-700">{{ __('report.stepper', ['current' => $position + 1, 'total' => count($chapterKeys), 'chapter' => __('report.chapters.'.$current)]) }}</span>
    @else
        <span></span>
    @endif
    <a href="{{ $nextUrl }}" class="font-semibold">{{ $nextKey === null ? __('report.next', ['chapter' => __('report.closing.title')]) : __('report.next', ['chapter' => __('report.chapters.'.$nextKey)]) }}</a>
</nav>
