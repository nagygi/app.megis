{{--
    A sample report for an invited member before their own (26 · 26.4): the
    report's own parts (the ring, the ladder, the rows) with made-up data, a
    coral "Minta" band on top, nothing links on. The real one comes after the
    mégis team analysed and approved it.
--}}
@php
    $values = collect(__('sample_report.values'));
    $processes = collect(__('sample_report.processes'));
    $average = collect([...$values, ...$processes])->avg('value');
@endphp

<x-layouts::client
    :title="__('sample_report.title')"
    color="light-yellow"
    symbol="growth-rings"
    :label="__('sample_report.title')"
    :subtitle="__('sample_report.subtitle')"
    :note="__('sample_report.organization')"
    :back="route('client.home', $organization)"
    :back-label="__('client.menu.home')"
>
    <div class="flex max-w-2xl flex-col gap-5">
        <div class="flex items-start gap-3 rounded-card bg-coral p-4 text-black" role="note">
            <span class="shrink-0 rounded-pill bg-black px-2.5 py-0.5 text-xs font-extrabold tracking-wide text-coral uppercase">{{ __('sample_report.band_title') }}</span>
            <p class="font-semibold">{{ __('sample_report.band') }}</p>
        </div>

        <p>{{ __('sample_report.lead') }}</p>

        <div class="flex items-center gap-4">
            <x-report.rings :percent="__('sample_report.percent')" :brand="false" />
            <div class="flex min-w-0 flex-1 flex-col gap-3">
                <x-report.ring-legend :percent="__('sample_report.percent')" :label="__('report.ring.label')" :points="__('sample_report.points')" />
            </div>
        </div>

        <x-report.ladder :value="$average" />

        <x-report.group :label="__('sample_report.values_label')">
            @foreach ($values as $row)
                <x-report.row :title="$row['name']" :meta="$row['meta']" :value="$row['value']" :number="\App\Services\Report\ReportText::decimal($row['value'])" :low="$row['value'] <= 2" />
            @endforeach
        </x-report.group>

        <x-report.group :label="__('sample_report.processes_label')">
            @foreach ($processes as $row)
                <x-report.row :title="$row['name']" :meta="$row['meta']" :value="$row['value']" :number="\App\Services\Report\ReportText::decimal($row['value'])" :low="$row['value'] <= 2" />
            @endforeach
        </x-report.group>

        <section class="flex flex-col gap-2 rounded-card bg-yellow-pale p-5" aria-labelledby="sample-takeaway">
            <h2 id="sample-takeaway" class="text-xs font-semibold tracking-[.08em] text-green uppercase">{{ __('sample_report.takeaway_title') }}</h2>
            <p class="text-lg leading-snug font-semibold">{{ __('sample_report.takeaway') }}</p>
        </section>

        <div>
            <x-ui.button tone="outline" icon="chevron-left" :href="route('client.home', $organization)">{{ __('sample_report.back') }}</x-ui.button>
        </div>
    </div>
</x-layouts::client>
