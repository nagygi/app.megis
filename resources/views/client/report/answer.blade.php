{{-- R0 · A válasz (27 · 27.2). --}}
@php($ring = $report['ring'])
<x-layouts::client :title="__('report.title')" color="light-yellow" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$homeUrl" :back-label="__('diagnosis.home_back')">
    <div class="flex max-w-2xl flex-col gap-6">
        <h2 class="text-2xl font-semibold">{{ __('report.question.answer') }}</h2>

        <section class="flex flex-col items-center gap-3 rounded-card bg-white p-6 text-center sm:flex-row sm:text-left" aria-label="{{ __('report.ring.label') }}">
            <x-report.ring :percent="$ring['percent']" />
            <div class="flex flex-col gap-1">
                <p class="text-lg font-semibold">{{ __('report.ring.label') }}</p>
                <p class="text-grey-700">{{ __('report.ring.points', ['points' => $ring['points'], 'max' => $ring['max'], 'count' => $ring['measuring_points']]) }}</p>
                <p class="text-sm text-grey-700">{{ __('report.ring.optimum') }}</p>
                @unless ($preview ?? false)
                    <a href="{{ route('client.wisdom.show', [$organization, 'szazalek-es-pontok']) }}" class="text-sm font-semibold">{{ __('report.ring.what') }}</a>
                @endunless
            </div>
        </section>

        <x-report.scale :value="$ring['step']" />

        <ul class="flex flex-col gap-2" aria-label="{{ __('report.question.internal') }}">
            @foreach ($report['chapters'] as $key => $chapter)
                <li>
                    <a href="{{ $url('chapter', [\App\Services\Report\ReportService::SLUGS[$key]]) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-charcoal no-underline hover:bg-paper">
                        <span class="font-semibold">{{ __('report.chapters.'.$key) }}</span>
                        <span class="flex items-center gap-2 text-sm tabular-nums">
                            {{ __('report.block_points', $chapter['points']) }}
                            <x-megis.icon name="chevron-right" class="size-4" />
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <a href="{{ $url('summary') }}" class="self-end font-semibold">{{ __('report.summary_link') }}</a>
    </div>
</x-layouts::client>
