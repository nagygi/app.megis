{{--
    The report PDF (28 · 28.3), the team view only. Teljes: R0 with R1, R1·b,
    the four chapters with their rows, the closing page and the Melléklet. A
    Belső snapshot as on screen: S1, S1·b and the Melléklet (27 · 27.10). A
    standalone diagnosis has no R1 and no R1·b (04 · 4.6).
--}}
@use('App\Services\Report\ReportText')
@php
    $meta = $report['meta'];
    $ring = $report['ring'];
    $recommendation = $report['texts']['recommendation'] ?? null;
    $standalone = $meta['standalone'] ?? false;
    $snapshot = $meta['snapshot'];
    $markdown = fn (string $text) => \Illuminate\Support\Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    $quote = fn (array $trace) => '„'.$trace['quote'].'”';
    $traceMeta = fn (array $trace) => collect([
        __('admin.reading.traces.sources.'.$trace['source']),
        $trace['role'] ? __('admin.reading.traces.roles.'.$trace['role']) : null,
        $trace['date'] ? \Illuminate\Support\Carbon::parse($trace['date'])->format('Y.m.d.') : null,
    ])->filter()->implode(' · ');
@endphp
<x-pdf-layout :title="$title">
    <section class="page">
        <p class="muted">{{ $meta['subject_name'] }} · {{ $meta['product'] }} · {{ __('report.frozen', ['date' => $date->format('m.d.')]) }}</p>
        <h1>{{ ReportText::copy('question.answer', $meta) }}</h1>
        @if ($snapshot && ($measured = collect($report['processes']['measured'])->pluck('name'))->isNotEmpty())
            <p class="muted">{{ __('report.snapshot_page.meta', ['processes' => $measured->implode(' és '), 'from' => $report['appendix']['window']['from'] ? \Illuminate\Support\Carbon::parse($report['appendix']['window']['from'])->format('m.d.') : '–', 'to' => \Illuminate\Support\Carbon::parse($report['appendix']['window']['to'])->format('m.d.')]) }}</p>
        @endif
        <p class="big">{{ $ring['percent'] === null ? '–' : $ring['percent'].'%' }}</p>
        <p>{{ __('report.ring.label') }} · {{ __('report.ring.points', ['points' => $ring['points'], 'max' => $ring['max'], 'count' => $ring['measuring_points']]) }}</p>
        <p class="muted">{{ __('report.band', ['step' => $ring['step'] === null ? '–' : $ring['step'].' '.__('report.scale.'.$ring['step'])]) }}</p>

        @if ($snapshot)
            {{-- S1: the breakdown by group, the values and the tree, then the process rows. --}}
            <table>
                @foreach (['values', 'brand'] as $key)
                    @isset($report['chapters'][$key])
                        <tr><td>{{ ReportText::groupTitle($key, count($report['chapters'][$key]['rows'])) }}</td><td class="num">{{ __('report.block_points', $report['chapters'][$key]['points']) }}</td></tr>
                    @endisset
                @endforeach
                @foreach ($report['chapters']['processes']['rows'] ?? [] as $row)
                    <tr><td>{{ $row['label'] }}</td><td class="num">{{ ($row['score_whole'] ?? '–').' / 6' }}</td></tr>
                @endforeach
            </table>
        @else
            <table>
                @foreach ($report['chapters'] as $key => $chapter)
                    <tr><td>{{ __('report.chapters.'.$key) }}</td><td class="num">{{ __('report.block_points', $chapter['points']) }}</td></tr>
                @endforeach
            </table>
        @endif

        @unless ($standalone)
            @unless ($snapshot)
                <h2>{{ __('report.question.summary') }}</h2>
                @if ($report['biggest_gap'])
                    <div class="box charcoal">{{ __('report.biggest_gap_sentence', ['label' => $report['biggest_gap']['label'], 'sentence' => ReportText::gap($report['biggest_gap'], $meta) ?? '']) }}</div>
                @elseif (! $meta['shows_experience'])
                    <div class="box charcoal">{{ __('report.biggest_gap') }}: {{ ReportText::missingPeers($meta) }}</div>
                @endif
            @endunless
            @foreach (['strength', 'crack'] as $kind)
                @if ($report[$kind])
                    <div class="box green"><strong>{{ ReportText::claimKind($kind, $meta) }} · {{ $report[$kind]['row'] }}</strong><br>{{ $report[$kind]['text'] }}@if ($report[$kind]['quote'])<br>„{{ $report[$kind]['quote'] }}”@endif</div>
                @endif
            @endforeach
        @endunless
        @if ($recommendation)
            <div class="box yellow"><strong>{{ __('report.recommendation') }}</strong>{!! $markdown($recommendation['text']) !!}
                @if ($recommendation['row'])<br>{{ __('report.recommendation_point', ['row' => $recommendation['row'], 'from' => $recommendation['from'] ?? '–', 'to' => $recommendation['to'] ?? '–']) }}@endif
            </div>
        @endif
    </section>

    @unless ($snapshot || $standalone)
        {{-- R1·b · Belső működés: every inner measuring point, by group, the highest first (27 · 27.4). --}}
        <section class="page">
            <h1>{{ __('report.pages.internal') }}</h1>
            <p class="muted">{{ __('report.internal_page.question') }}</p>
            @foreach ($report['chapters'] as $key => $chapter)
                <h3>{{ __('report.groups.points', ['label' => ReportText::groupTitle($key, count($chapter['rows'])), 'points' => $chapter['points']['points'], 'max' => $chapter['points']['max']]) }}</h3>
                <table>
                    @foreach (collect($chapter['rows'])->sortByDesc(fn (array $row) => $row['score'] ?? -1) as $row)
                        @php($marker = match (true) {
                            $row['is_biggest_gap'] => __('report.biggest_gap_short'),
                            $row['has_crack'] => __('report.crack_marker'),
                            default => null,
                        })
                        <tr><td>{{ $row['label'] }}@if ($marker) <span class="muted">· {{ $marker }}</span>@endif</td><td class="num">{{ ReportText::score($row) }}</td></tr>
                    @endforeach
                </table>
            @endforeach
        </section>
    @endunless

    @foreach ($report['chapters'] as $key => $chapter)
        {{-- The snapshot shows only its brand base as a page of its own (S1·b). --}}
        @continue($snapshot && $key !== 'brand')
        <section class="page">
            <h1>{{ $snapshot ? __('report.snapshot.brand_link') : __('report.chapters.'.$key) }}</h1>
            <p class="muted">{{ ReportText::copy('question.'.$key, $meta) }}</p>
            @if (filled($report['texts']['summary_'.$key] ?? null))
                <p>{{ $report['texts']['summary_'.$key] }}</p>
            @endif
            @if ($key === 'brand' && filled($report['texts']['brand_reading'] ?? null))
                <div class="box yellow"><strong>{{ __('report.brand_reading') }}</strong>{!! $markdown($report['texts']['brand_reading']) !!}</div>
            @endif
            @foreach ($chapter['rows'] as $row)
                <h3>{{ $row['label'] }} · {{ ReportText::score($row) }}</h3>
                @if ($sentence = ReportText::gap($row, $meta))
                    <p>{{ $sentence }}</p>
                @endif
                @if ($row['occasions'])
                    <p class="muted">{{ __('report.occasions', ['count' => $row['occasions']['count'], 'yes' => $row['occasions']['yes'] ?? 0, 'partial' => $row['occasions']['partial'] ?? 0, 'no' => $row['occasions']['no'] ?? 0]) }}</p>
                @endif
                @if ($row['definition'] && filled($row['definition']['meaning']))
                    <p class="muted">{{ ReportText::copy('definition', $meta) }}: {{ $row['definition']['meaning'] }}</p>
                @endif
                @foreach ($row['claims'] as $claim)
                    <p><strong>{{ ReportText::claimKind($claim['kind'], $meta) }}:</strong> {{ $claim['text'] }}</p>
                @endforeach
                @foreach (array_slice($row['traces'], 0, 3) as $trace)
                    <div class="box quote">{{ $quote($trace) }}<br><span class="muted">{{ $traceMeta($trace) }}</span></div>
                @endforeach
            @endforeach
        </section>
    @endforeach

    <section class="page">
        @unless ($snapshot)
            <h1>{{ ReportText::copy('closing.title', $meta) }}</h1>
            <p>{{ __('report.closing.quiet', ['percent' => $ring['percent'] ?? '–', 'label' => $report['biggest_gap']['label'] ?? '–']) }}</p>
            @if ($recommendation)
                <div class="box yellow">{!! $markdown($recommendation['text']) !!}{{ ReportText::copy('closing.hour', $meta) }}</div>
            @endif
        @endunless

        @include('pdf.partials.appendix')
    </section>
</x-pdf-layout>
