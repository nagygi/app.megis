{{-- R2 · R3 · R4 · R5: a chapter (27 · 27.5–27.8, prototype R2–R5); S1·b for a snapshot's brand base; a standalone diagnosis' chapter (04 · 4.6). --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@use('App\Enums\TreeElement')
@php
    $key = $chapter['key'];
    $slug = ReportService::SLUGS[$key];
    $kind = \App\Services\Report\ReportBuilder::CHAPTERS[$key]->value;
    $meta = $report['meta'];
    $standalone = $meta['standalone'] ?? false;
    $viewRows = $mode === 'csapat' ? null : ($mode === 'egyen' ? $individual : $organizationView)->where('kind', $kind)->values();
    $rows = collect($chapter['rows']);
    $points = $chapter['points'];
    $symbols = ['values' => 'root', 'processes' => 'mycelium', 'areas' => 'circle', 'brand' => 'tree-of-life'];

    // Back to the previous chapter; from the first one to R1, or R0 without it.
    $previous = $chapterKeys[array_search($key, $chapterKeys, true) - 1] ?? null;
    [$back, $backLabel] = match (true) {
        $meta['snapshot'] => [$url('show'), __('report.pages.snapshot')],
        $previous !== null => [$url('chapter', [ReportService::SLUGS[$previous]]), __('report.chapters.'.$previous)],
        $standalone => [$url('show'), __('report.pages.answer')],
        default => [$url('summary'), __('report.dots.summary')],
    };

    $processes = collect([...$report['processes']['measured'], ...$report['processes']['others']]);
    $note = match (true) {
        $key === 'brand' => __('report.brand_meta', ['count' => mb_ucfirst(\App\Services\Report\HungarianNumber::word($rows->count())), 'inside' => ReportText::decimal($chapter['average'])]),
        $key === 'processes' && $processes->isNotEmpty() => __('report.process_meta', [
            'count' => $processes->count(),
            'calendar' => $processes->where('in_calendar', true)->count(),
            'missing' => $processes->where('in_calendar', false)->count(),
            'measured' => count($report['processes']['measured']),
        ]),
        default => __('report.groups.points', ['label' => ReportText::groupTitle($key, $rows->count()), 'points' => $points['points'], 'max' => $points['max']]),
    };
    $low = fn (array $row) => ($row['score_whole'] ?? null) !== null && $row['score_whole'] <= 2;
@endphp
<x-layouts::client
    :title="__('report.chapters.'.$key)"
    :color="$key === 'brand' ? 'light-yellow' : 'green'"
    :symbol="$symbols[$key]"
    :label="__('report.chapters.'.$key)"
    :subtitle="ReportText::copy('question.'.$key, $meta)"
    :note="$note"
    :dots="$dots"
    :back="$back"
    :back-label="$backLabel"
>
    <div class="flex max-w-2xl flex-col gap-5">
        @include('client.report.partials.updated', ['chapterKey' => $key])
        @if (filled($report['texts']['summary_'.$key] ?? null))
            <p class="text-grey-700">{{ $report['texts']['summary_'.$key] }}</p>
        @endif

        @unless ($meta['snapshot'] || $standalone)
            @include('client.report.partials.switch')
        @endunless

        @if ($key === 'processes' && $processes->isNotEmpty())
            {{-- The process map: measured ones filled, a coral ring where it is not in the calendar (27 · 27.6). --}}
            <section class="flex flex-col gap-2" aria-labelledby="process-map">
                <h2 id="process-map" class="text-xs font-semibold tracking-[.08em] text-green uppercase">{{ __('report.process_map') }}</h2>
                <ul class="flex flex-wrap gap-1.5">
                    @foreach ($report['processes']['measured'] as $process)
                        <li @class(['rounded-pill bg-charcoal px-3 py-1 text-sm font-semibold text-cream', 'outline-[1.5px] outline-coral' => ! $process['in_calendar']])>{{ $process['name'] }} · {{ __('report.measured') }}@unless ($process['in_calendar']) · {{ __('report.not_in_calendar') }}@endunless</li>
                    @endforeach
                    @foreach ($report['processes']['others'] as $process)
                        <li @class(['rounded-pill border px-3 py-1 text-sm', 'border-charcoal' => $process['in_calendar'], 'border-coral' => ! $process['in_calendar']])>{{ $process['name'] }}@unless ($process['in_calendar']) · {{ __('report.not_in_calendar') }}@endunless</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($key === 'brand')
            <x-report.tree-circle :rows="$chapter['rows']" :average="$chapter['average']" />

            @if (filled($report['texts']['brand_reading'] ?? null))
                <x-ui.card as="section" :bordered="false" padding="sm" class="flex flex-col gap-2 border-2 border-coral" aria-labelledby="brand-reading">
                    <h2 id="brand-reading" class="flex items-center gap-1.5 text-xs font-semibold tracking-[.07em] uppercase"><x-megis.symbol name="spiral" class="size-4 text-coral" />{{ __('report.brand_reading') }}</h2>
                    <div class="prose-megis text-sm">{!! \Illuminate\Support\Str::markdown($report['texts']['brand_reading'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                    <p class="text-xs text-grey-700">{{ __('report.brand_reading_marker') }}</p>
                </x-ui.card>
            @endif
        @endif

        @if ($viewRows !== null)
            {{-- One person's or the organization's view: no deepening, the gap speaks to one person. --}}
            <div class="flex flex-col">
                @forelse ($viewRows as $row)
                    @php
                        $whole = $row['score'] === null ? null : (int) floor($row['score'] + 0.5);
                        $rowMeta = $row['gap'] !== null && $row['experience'] !== null && $whole !== null
                            ? __($mode === 'egyen' || ! $meta['has_peers'] ? 'report.gap_sentence_self' : 'report.gap_sentence', ['experience' => \App\Services\Report\HungarianNumber::dative((int) floor($row['experience'] + 0.5)), 'score' => \App\Services\Report\HungarianNumber::accusative($whole)])
                            : null;
                    @endphp
                    <x-report.row
                        :title="$row['label']"
                        :meta="$rowMeta"
                        :value="$row['score']"
                        :number="$row['score_state'] === 'insufficient_data' ? __('report.insufficient') : ($whole ?? '–')"
                        :low="$whole !== null && $whole <= 2"
                    />
                @empty
                    <x-ui.empty-state>{{ __('report.insufficient') }}</x-ui.empty-state>
                @endforelse
            </div>
        @elseif ($key === 'brand')
            {{-- Elemenként · a sor lenyílik: inside with its PHI, outside coming (prototype R5). --}}
            <x-report.group :label="__('report.tree.list')">
                @foreach ($rows as $row)
                    @php($symbol = TreeElement::from($row['element'])->symbol())
                    <details id="element-{{ $row['id'] }}" class="group border-b border-grey-200">
                        <summary class="press flex cursor-pointer list-none items-center gap-2.5 py-3 [&::-webkit-details-marker]:hidden">
                            <x-megis.symbol :name="$symbol" class="size-6" />
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold">{{ __('mirror.elements.'.$symbol) }}</span>
                                <span class="block text-xs text-grey-700">{{ \Illuminate\Support\Str::before($row['label'], ' (') }}</span>
                            </span>
                            <span @class(['grid h-6 min-w-7 place-items-center rounded-pill px-1.5 text-xs font-extrabold', 'bg-green text-cream' => $row['score_state'] === 'scored', 'border border-dashed border-grey-600 text-grey-700' => $row['score_state'] !== 'scored'])>
                                <span class="sr-only">{{ __('report.tree.inside') }}</span>{{ ReportText::score($row) }}
                            </span>
                            <span class="grid h-6 min-w-7 place-items-center rounded-pill border border-dashed border-grey-600 px-1.5 text-xs font-semibold text-grey-700" title="{{ __('report.tree.outside') }} · {{ __('report.soon') }}">
                                {{ __('report.tree.outside_short') }} –<span class="sr-only"> {{ __('report.tree.outside') }} · {{ __('report.soon') }}</span>
                            </span>
                            <x-megis.icon name="chevron-down" class="shrink-0 text-grey-600 transition-transform group-open:rotate-180" />
                        </summary>
                        <div class="flex flex-col gap-3 pb-4">
                            <div class="flex flex-col gap-1">
                                <p class="text-xs font-semibold tracking-[.08em] text-green uppercase">{{ __('report.tree.inside') }} · {{ __('report.phi') }} {{ ReportText::score($row) }}</p>
                                @if ($sentence = ReportText::gap($row, $meta))
                                    <p class="text-sm">{{ $sentence }}</p>
                                @endif
                                @forelse ($row['claims'] as $claim)
                                    <p class="text-sm">{{ $claim['text'] }}</p>
                                @empty
                                    @if ($row['traces'][0] ?? null)
                                        @include('client.report.partials.quote', ['trace' => $row['traces'][0]])
                                    @endif
                                @endforelse
                            </div>
                            <p class="flex items-center gap-2 text-xs font-semibold tracking-[.08em] text-green uppercase">{{ __('report.tree.outside') }} <x-report.soon /></p>
                            <a href="{{ $url('item', [$slug, $row['id']]) }}" class="self-start text-sm font-semibold text-charcoal">{{ __('report.element_evidence') }} <x-megis.icon name="chevron-right" class="inline size-3.5 align-middle" /></a>
                        </div>
                    </details>
                @endforeach
                <x-report.row :title="__('report.groups.tree_more')" soon symbol="mycelium" />
            </x-report.group>
        @else
            <div class="flex flex-col">
                @foreach ($rows as $row)
                    @php($rowMeta = $row['occasions'] ? __('report.occasions', ['count' => $row['occasions']['count'], 'yes' => $row['occasions']['yes'] ?? 0, 'partial' => $row['occasions']['partial'] ?? 0, 'no' => $row['occasions']['no'] ?? 0]) : ReportText::gap($row, $meta))
                    <x-report.row
                        :href="$url('item', [$slug, $row['id']])"
                        :title="$row['label']"
                        :meta="$rowMeta"
                        :meta-tone="($row['is_biggest_gap'] || $row['has_crack']) && $meta['shows_experience'] ? 'coral' : null"
                        :value="$row['score']"
                        :number="ReportText::score($row)"
                        :low="$low($row)"
                    />
                @endforeach
            </div>
        @endif

        {{-- The claims of the chapter with their first quote; at the tree, the cracks between the sides. --}}
        @foreach ($rows as $row)
            @foreach ($row['claims'] as $claim)
                @continue($key === 'brand' && $claim['kind'] !== 'crack')
                @include('client.report.partials.claim', ['claim' => $claim, 'claimRow' => $row['label'], 'evidenceUrl' => $url('item', [$slug, $row['id']])])
            @endforeach
        @endforeach

        @if (! $meta['snapshot'] && ! $preview && $main !== null)
            <livewire:client.report.feedback :main="$main" :chapter="$key" :key="'feedback-'.$key" />
        @endif

        <div class="flex flex-col">
            @if (! $preview && in_array($key, ['brand', 'areas'], true))
                <x-report.row
                    :href="route('client.wisdom.show', [$organization, $key === 'brand' ? 'kuldetesfa' : 'teruletek'])"
                    :title="$key === 'brand' ? __('report.what_is_tree') : __('report.chapters.areas')"
                    :meta="__('wisdom.title')"
                    symbol="growth-rings"
                    :bar="false"
                />
            @endif
            <x-report.row :href="$url('appendix')" :title="__('report.appendix_link')" :meta="__('report.menu.appendix_meta')" symbol="circle" :bar="false" />
        </div>

        <x-report.footer :status="$nav['status']" :next="$nav['next']" :next-label="$nav['nextLabel']" />
    </div>
</x-layouts::client>
