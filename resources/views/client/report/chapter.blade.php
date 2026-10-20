{{-- R2 · R3 · R4 · R5: a chapter of the internal working (27 · 27.5–27.8); S1·b for a snapshot's brand base. --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@php
    $key = $chapter['key'];
    $slug = ReportService::SLUGS[$key];
    $kind = \App\Services\Report\ReportBuilder::CHAPTERS[$key]->value;
    $viewRows = $mode === 'csapat' ? null : ($mode === 'egyen' ? $individual : $organizationView)->where('kind', $kind)->values();
    $color = $key === 'brand' ? 'light-yellow' : 'green';
@endphp
<x-layouts::client :title="__('report.chapters.'.$key)" :color="$color" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$url($report['meta']['snapshot'] ? 'show' : 'internal')" :back-label="$report['meta']['snapshot'] ? __('report.title') : __('report.question.internal')">
    <div class="flex max-w-2xl flex-col gap-6">
        <div class="flex flex-col gap-2">
            <h2 class="text-xl font-semibold">{{ __('report.question.'.$key) }}</h2>
            @include('client.report.partials.updated', ['chapterKey' => $key])
            @if (filled($report['texts']['summary_'.($key === 'brand' ? 'brand' : $key)] ?? null))
                <p class="text-grey-700">{{ $report['texts']['summary_'.($key === 'brand' ? 'brand' : $key)] }}</p>
            @endif
        </div>

        @include('client.report.partials.switch')

        @if ($key === 'processes')
            {{-- The process map with chips (27 · 27.6) --}}
            <section class="flex flex-col gap-2" aria-labelledby="process-map">
                <h3 id="process-map" class="font-semibold">{{ __('report.process_map') }}</h3>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($report['processes']['measured'] as $process)
                        <li class="rounded-pill bg-charcoal px-3 py-1 text-sm text-cream">{{ $process['name'] }} · {{ __('report.measured') }}@unless ($process['in_calendar']) · {{ __('report.not_in_calendar') }}@endunless</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($key === 'brand')
            {{-- The tree with its four elements and the PHI of the tree (27 · 27.8) --}}
            <x-ui.card as="section" padding="lg" class="flex flex-col items-center gap-4" aria-label="{{ __('report.chapters.brand') }}">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($chapter['rows'] as $row)
                        <a href="#element-{{ $row['id'] }}" class="flex flex-col items-center gap-1 text-center text-charcoal no-underline">
                            <span @class(['grid size-16 place-items-center rounded-pill', 'border-2 border-dashed border-grey-350' => $row['score_state'] !== 'scored', 'bg-yellow-pale' => $row['score_state'] === 'scored'])>
                                <x-megis.symbol :name="\App\Enums\TreeElement::from($row['element'])->symbol()" class="size-10" />
                            </span>
                            <span class="text-sm font-semibold">{{ $row['label'] }}</span>
                            <span class="text-sm tabular-nums">{{ ReportText::score($row) }}</span>
                        </a>
                    @endforeach
                </div>
                <p class="font-semibold">{{ __('report.phi') }} {{ ReportText::decimal($chapter['average']) }}</p>
            </x-ui.card>

            @if (filled($report['texts']['brand_reading'] ?? null))
                <section class="flex flex-col gap-2 rounded-card bg-yellow-pale p-5" aria-labelledby="brand-reading">
                    <h3 id="brand-reading" class="font-semibold">{{ __('report.brand_reading') }}</h3>
                    <div class="prose-megis">{!! \Illuminate\Support\Str::markdown($report['texts']['brand_reading'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                    <p class="text-xs text-grey-700">{{ __('report.brand_reading_marker') }}</p>
                </section>
            @endif
        @endif

        @if ($viewRows !== null)
            <ul class="flex flex-col gap-2">
                @forelse ($viewRows as $row)
                    <x-ui.card as="li" padding="none" class="flex flex-col gap-1 px-5 py-4">
                        <span class="flex items-center justify-between gap-3">
                            <span class="font-semibold">{{ $row['label'] }}</span>
                            <span class="text-lg font-semibold tabular-nums">{{ $row['score_state'] === 'insufficient_data' ? __('report.insufficient') : ($row['score'] === null ? '–' : (int) floor($row['score'] + 0.5)) }}</span>
                        </span>
                        @if ($row['gap'] !== null && $row['experience'] !== null && $row['score'] !== null)
                            {{-- One person's view speaks to one person. --}}
                            <span class="text-sm text-grey-700">{{ __($mode === 'egyen' || ! $report['meta']['has_peers'] ? 'report.gap_sentence_self' : 'report.gap_sentence', ['experience' => \App\Services\Report\HungarianNumber::dative((int) floor($row['experience'] + 0.5)), 'score' => \App\Services\Report\HungarianNumber::accusative((int) floor($row['score'] + 0.5))]) }}</span>
                        @endif
                    </x-ui.card>
                @empty
                    <li><x-ui.empty-state>{{ __('report.insufficient') }}</x-ui.empty-state></li>
                @endforelse
            </ul>
        @else
            <ul class="flex flex-col gap-3">
                @foreach ($chapter['rows'] as $row)
                    <x-ui.card as="li" id="element-{{ $row['id'] }}" class="flex flex-col gap-3">
                        <a href="{{ $url('item', [$slug, $row['id']]) }}" class="flex items-start justify-between gap-3 text-charcoal no-underline">
                            <span>
                                <span class="block text-lg font-semibold">{{ $row['label'] }}</span>
                                @if ($row['occasions'])
                                    <span class="block text-sm text-grey-700">{{ __('report.occasions', ['count' => $row['occasions']['count'], 'yes' => $row['occasions']['yes'] ?? 0, 'partial' => $row['occasions']['partial'] ?? 0, 'no' => $row['occasions']['no'] ?? 0]) }}</span>
                                @endif
                                @if ($sentence = ReportText::gap($row, $report['meta']))
                                    <span @class(['block text-sm', 'text-grey-600' => ! $report['meta']['shows_experience'], 'text-grey-700' => $report['meta']['shows_experience']])>{{ $sentence }}</span>
                                @endif
                            </span>
                            <span class="text-2xl font-extrabold tabular-nums">{{ ReportText::score($row) }}</span>
                        </a>
                        @foreach ($row['claims'] as $claim)
                            @include('client.report.partials.claim', ['claim' => $claim, 'evidenceUrl' => $url('item', [$slug, $row['id']])])
                        @endforeach
                        @if ($key === 'brand' && $row['claims'] === [] && ($row['traces'][0] ?? null))
                            @include('client.report.partials.quote', ['trace' => $row['traces'][0]])
                        @endif
                        @if ($key === 'brand')
                            <x-ui.button tone="ghost" size="sm" icon-right="chevron-right" class="-ml-1.5 self-start" :href="$url('item', [$slug, $row['id']])">{{ __('report.element_evidence') }}</x-ui.button>
                        @endif
                    </x-ui.card>
                @endforeach
            </ul>
        @endif

        @if ($key === 'processes' && $report['processes']['others'] !== [])
            <section class="flex flex-col gap-2" aria-labelledby="not-measured">
                <h3 id="not-measured" class="font-semibold">{{ __('report.not_measured') }}</h3>
                <ul class="flex flex-wrap gap-2 text-sm">
                    @foreach ($report['processes']['others'] as $process)
                        <li class="rounded-pill border border-grey-350 px-3 py-1">{{ $process['name'] }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (! $report['meta']['snapshot'] && ! $preview)
            <livewire:client.report.feedback :main="$main" :chapter="$key" :key="'feedback-'.$key" />
        @endif

        @if (! $preview && in_array($key, ['brand', 'areas'], true))
            <x-ui.button tone="ghost" size="sm" class="-ml-1.5 self-start" :href="route('client.wisdom.show', [$organization, $key === 'brand' ? 'kuldetesfa' : 'teruletek'])">{{ $key === 'brand' ? __('report.what_is_tree') : __('wisdom.title').' · '.__('report.chapters.areas') }}</x-ui.button>
        @endif
        <x-ui.button tone="ghost" size="sm" icon="document" class="-ml-1.5 self-start" :href="$url('appendix')">{{ __('report.appendix_link') }}</x-ui.button>

        @if ($report['meta']['snapshot'])
            <x-ui.page-nav :label="__('report.title')" :back="$url('show')" :back-label="__('report.title')" />
        @else
            @include('client.report.partials.footer', ['current' => $key])
        @endif
    </div>
</x-layouts::client>
