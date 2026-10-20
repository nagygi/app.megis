{{-- R9 · Zárólap (27 · 27.11). --}}
@php($recommendation = $report['texts']['recommendation'] ?? null)
<x-layouts::client :title="__('report.title')" color="light-yellow" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$url('show')" :back-label="__('report.ring.label')">
    <div class="flex max-w-2xl flex-col gap-6">
        <div class="flex flex-col gap-2">
            <h2 class="text-xl font-semibold">{{ __('report.closing.title') }}</h2>
            <p class="text-grey-700">{{ __('report.closing.lead') }}</p>
        </div>

        <p class="text-sm text-grey-700">{{ __('report.closing.quiet', ['percent' => $report['ring']['percent'] ?? '–', 'label' => $report['biggest_gap']['label'] ?? '–']) }}</p>

        @if ($recommendation)
            <section class="flex flex-col gap-2 rounded-card bg-yellow p-5" aria-labelledby="closing-recommendation">
                <h3 id="closing-recommendation" class="font-semibold">{{ __('report.recommendation') }}</h3>
                <p>{{ $recommendation['text'] }}</p>
                @if ($recommendation['row'])
                    <p class="text-sm font-semibold">{{ __('report.recommendation_point', ['row' => $recommendation['row'], 'from' => $recommendation['from'] ?? '–', 'to' => $recommendation['to'] ?? '–']) }}</p>
                @endif
                <p class="text-sm">{{ __('report.closing.hour') }}</p>
            </section>
        @endif

        @if (filled(config('megis.booking_url')))
            <div>
                <x-ui.button size="lg" icon-right="arrow-right" :href="config('megis.booking_url')" target="_blank" rel="noopener">
                    {{ __('report.closing.book') }}
                    <span class="sr-only">({{ __('ui.new_tab') }})</span>
                </x-ui.button>
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            @unless ($preview)
                <x-ui.button tone="outline" size="sm" icon="document" :href="$url('pdf')">{{ __('report.closing.pdf') }}</x-ui.button>
            @endunless
            <x-ui.button tone="outline" size="sm" icon="document" :href="$url('appendix')">{{ __('report.appendix_link') }}</x-ui.button>
        </div>
    </div>
</x-layouts::client>
