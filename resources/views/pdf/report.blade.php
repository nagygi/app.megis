{{-- The report PDF (28, question 4): R0, R1, R1·b, the four chapters with their rows, the closing page and the Melléklet; the team view only. --}}
@use('App\Services\Report\ReportText')
@php
    $meta = $report['meta'];
    $ring = $report['ring'];
    $recommendation = $report['texts']['recommendation'] ?? null;
    $appendix = $report['appendix'];
@endphp
<x-pdf-layout :title="$title">
    <section class="page">
        <p class="muted">{{ $meta['subject_name'] }} · {{ $meta['product'] }} · {{ __('report.frozen', ['date' => $date->format('m.d.')]) }}</p>
        <h1>{{ __('report.question.answer') }}</h1>
        <p class="big">{{ $ring['percent'] === null ? '–' : $ring['percent'].'%' }}</p>
        <p>{{ __('report.ring.label') }} · {{ __('report.ring.points', ['points' => $ring['points'], 'max' => $ring['max'], 'count' => $ring['measuring_points']]) }}</p>
        <p class="muted">{{ __('report.band', ['step' => $ring['step'] === null ? '–' : $ring['step'].' '.__('report.scale.'.$ring['step'])]) }}</p>
        <table>
            @foreach ($report['chapters'] as $key => $chapter)
                <tr><td>{{ __('report.chapters.'.$key) }}</td><td class="num">{{ __('report.block_points', $chapter['points']) }}</td></tr>
            @endforeach
        </table>

        @unless ($meta['snapshot'])
            <h2>{{ __('report.question.summary') }}</h2>
            @if ($report['biggest_gap'])
                <div class="box charcoal">{{ __('report.biggest_gap_sentence', ['label' => $report['biggest_gap']['label'], 'sentence' => ReportText::gap($report['biggest_gap'], $meta) ?? '']) }}</div>
            @endif
            @foreach (['strength', 'crack'] as $kind)
                @if ($report[$kind])
                    <div class="box green"><strong>{{ ReportText::claimKind($kind, $meta) }} · {{ $report[$kind]['row'] }}</strong><br>{{ $report[$kind]['text'] }}</div>
                @endif
            @endforeach
        @endunless
        @if ($recommendation)
            <div class="box yellow"><strong>{{ __('report.recommendation') }}</strong><br>{{ $recommendation['text'] }}
                @if ($recommendation['row'])<br>{{ __('report.recommendation_point', ['row' => $recommendation['row'], 'from' => $recommendation['from'] ?? '–', 'to' => $recommendation['to'] ?? '–']) }}@endif
            </div>
        @endif
    </section>

    @foreach ($report['chapters'] as $key => $chapter)
        <section class="page">
            <h1>{{ __('report.chapters.'.$key) }}</h1>
            <p class="muted">{{ __('report.question.'.$key) }}</p>
            @if (filled($report['texts']['summary_'.($key === 'brand' ? 'brand' : $key)] ?? null))
                <p>{{ $report['texts']['summary_'.($key === 'brand' ? 'brand' : $key)] }}</p>
            @endif
            @if ($key === 'brand' && filled($report['texts']['brand_reading'] ?? null))
                <div class="box yellow"><strong>{{ __('report.brand_reading') }}</strong><br>{{ $report['texts']['brand_reading'] }}</div>
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
                    <p class="muted">{{ __('report.definition') }}: {{ $row['definition']['meaning'] }}</p>
                @endif
                @foreach ($row['claims'] as $claim)
                    <p><strong>{{ ReportText::claimKind($claim['kind'], $meta) }}:</strong> {{ $claim['text'] }}</p>
                @endforeach
                @foreach (array_slice($row['traces'], 0, 3) as $trace)
                    <div class="box quote">„{{ $trace['quote'] }}”<br><span class="muted">{{ __('admin.reading.traces.sources.'.$trace['source']) }}@if ($trace['role']) · {{ __('admin.reading.traces.roles.'.$trace['role']) }}@endif @if ($trace['date']) · {{ \Illuminate\Support\Carbon::parse($trace['date'])->format('Y.m.d.') }}@endif</span></div>
                @endforeach
            @endforeach
        </section>
    @endforeach

    <section class="page">
        <h1>{{ __('report.closing.title') }}</h1>
        <p>{{ __('report.closing.quiet', ['percent' => $ring['percent'] ?? '–', 'label' => $report['biggest_gap']['label'] ?? '–']) }}</p>
        @if ($recommendation)
            <div class="box yellow">{{ $recommendation['text'] }}<br>{{ __('report.closing.hour') }}</div>
        @endif

        <h2>{{ __('appendix.pdf_title') }}</h2>
        <p>{{ __('appendix.read_by_text', ['date' => $date->format('Y.m.d.')]) }} · {{ __('appendix.from_text', $appendix['sources']) }}</p>
        <h3>{{ __('appendix.overrides') }}</h3>
        @forelse ($appendix['overrides'] as $override)
            <p>{{ $override['label'] }} · {{ ReportText::decimal($override['from']) }} → {{ ReportText::decimal($override['to']) }} · {{ $override['reason'] }} <span class="muted">({{ __('appendix.override_by', ['date' => \Illuminate\Support\Carbon::parse($override['date'])->format('Y.m.d.')]) }})</span></p>
        @empty
            <p class="muted">{{ __('appendix.no_overrides') }}</p>
        @endforelse
        <h3>{{ __('appendix.rows', ['count' => count($appendix['rows'])]) }}</h3>
        <table>
            @foreach ($appendix['rows'] as $row)
                <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['score_state'] === 'scored' ? ReportText::decimal($row['score']) : '–' }}</td></tr>
            @endforeach
        </table>
    </section>
</x-pdf-layout>
