{{-- R0 · A válasz (27 · 27.2). --}}
@php($ring = $report['ring'])
<x-layouts::client :title="__('report.title')" color="light-yellow" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$homeUrl" :back-label="__('diagnosis.home_back')">
    <div class="flex max-w-2xl flex-col gap-6">
        <h2 class="text-xl font-semibold">{{ __('report.question.answer') }}</h2>

        <x-ui.card as="section" padding="lg" class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left" aria-label="{{ __('report.ring.label') }}">
            <x-report.ring :percent="$ring['percent']" />
            <div class="flex flex-col items-center gap-1 sm:items-start">
                <p class="text-lg font-semibold">{{ __('report.ring.label') }}</p>
                <p class="text-grey-700">{{ __('report.ring.points', ['points' => $ring['points'], 'max' => $ring['max'], 'count' => $ring['measuring_points']]) }}</p>
                <p class="text-sm text-grey-700">{{ __('report.ring.optimum') }}</p>
                @unless ($preview ?? false)
                    <x-ui.button tone="ghost" size="sm" :href="route('client.wisdom.show', [$organization, 'szazalek-es-pontok'])">{{ __('report.ring.what') }}</x-ui.button>
                @endunless
            </div>
        </x-ui.card>

        <x-report.scale :value="$ring['step']" />

        <ul class="flex flex-col gap-2" aria-label="{{ __('report.question.internal') }}">
            @foreach ($report['chapters'] as $key => $chapter)
                <li>
                    <x-ui.link-row :href="$url('chapter', [\App\Services\Report\ReportService::SLUGS[$key]])" :title="__('report.chapters.'.$key)">
                        <x-slot:trailing>{{ __('report.block_points', $chapter['points']) }}</x-slot:trailing>
                    </x-ui.link-row>
                </li>
            @endforeach
        </ul>

        <x-ui.page-nav :label="__('report.title')" :next="$url('summary')" :next-label="__('report.summary_link')" />
    </div>
</x-layouts::client>
