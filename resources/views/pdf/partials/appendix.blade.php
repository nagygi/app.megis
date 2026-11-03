{{-- RM in print (28 · 28.2, 28.3): Kire, Miből, Mikor, Ki olvasta, the quotes, the claims, what the mégis wrote over and the measuring points. --}}
@use('App\Services\Report\ReportText')
@php
    $appendix = $report['appendix'];
    $meta = $report['meta'];
    $standalone = $meta['standalone'] ?? false;
    $day = fn (?string $value) => $value === null ? '–' : \Illuminate\Support\Carbon::parse($value)->format('Y.m.d.');
@endphp
<h2>{{ __('appendix.pdf_title') }}</h2>
<table>
    <tr><th>{{ __('appendix.who') }}</th><td>{{ $meta['has_peers']
        ? __('appendix.who_text', ['leader' => $appendix['who']['leader'], 'subject' => $appendix['who']['subject'], 'peers' => $appendix['who']['peers'], 'answered' => $appendix['who']['answered']])
        : __('appendix.who_alone', ['leader' => $appendix['who']['leader']]) }}</td></tr>
    {{-- The calendar stays out of the print (28 · 28.2). --}}
    <tr><th>{{ __('appendix.from') }}</th><td>{{ $standalone ? ReportText::standaloneSource($appendix['source']) : __('appendix.from_text', $appendix['sources']) }}</td></tr>
    @isset($appendix['window'])
        <tr><th>{{ __('appendix.when') }}</th><td>{{ $day($appendix['window']['from']) }} – {{ $day($appendix['window']['to']) }}</td></tr>
    @endisset
    <tr><th>{{ __('appendix.read_by') }}</th><td>{{ __('appendix.read_by_text', ['date' => $day($appendix['read_by']['published_on'] ?? null)]) }}</td></tr>
</table>

<h3>{{ __('appendix.quotes', ['count' => count($appendix['quotes'])]) }}</h3>
@forelse ($appendix['quotes'] as $trace)
    <div class="box quote">„{{ $trace['quote'] }}”<br><span class="muted">{{ __('admin.reading.traces.sources.'.$trace['source']) }}@if ($trace['role']) · {{ __('admin.reading.traces.roles.'.$trace['role']) }}@endif @if ($trace['date']) · {{ $day($trace['date']) }}@endif</span></div>
@empty
    <p class="muted">–</p>
@endforelse

@unless ($standalone)
    <h3>{{ __('appendix.claims', $appendix['claims']['counts']) }}</h3>
    <table>
        @foreach ($appendix['claims']['list'] as $claim)
            <tr><td>{{ ReportText::claimKind($claim['kind'], $meta) }} · {{ $claim['row'] }}<br>{{ $claim['text'] }}</td><td class="num">{{ trans_choice('report.evidence', $claim['traces'], ['count' => $claim['traces']]) }}</td></tr>
        @endforeach
    </table>
@endunless

<h3>{{ __('appendix.overrides') }}</h3>
@forelse ($appendix['overrides'] as $override)
    <p>{{ $override['label'] }} · {{ ReportText::decimal($override['from']) }} → {{ ReportText::decimal($override['to']) }}@if (filled($override['sentence'] ?? null)) · {{ __('appendix.override_sentence', ['sentence' => $override['sentence']]) }}@endif · {{ $override['reason'] }} <span class="muted">({{ __('appendix.override_by', ['date' => $day($override['date'])]) }})</span></p>
@empty
    <p class="muted">{{ __('appendix.no_overrides') }}</p>
@endforelse

<h3>{{ __('appendix.rows', ['count' => count($appendix['rows'])]) }}</h3>
<table>
    @foreach ($appendix['rows'] as $row)
        <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['score_state'] === 'scored' ? ReportText::decimal($row['score']) : ($row['score_state'] === 'insufficient_data' ? __('report.insufficient') : '–') }}</td></tr>
    @endforeach
</table>
