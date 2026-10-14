{{-- The Útravaló PDF (28 · 28.3, 29): experience only, no PHI, no other name. --}}
@php($whole = fn ($number) => $number === null ? '–' : (int) floor($number + 0.5))
<x-pdf-layout :title="$title">
    <section class="page">
        <p class="muted">{{ __('takeaway.subtitle', ['date' => $date->format('m.d.'), 'subject' => $takeaway['subject_name']]) }}</p>
        <h1>{{ __('takeaway.title') }}</h1>
        <p>{{ __('takeaway.lead') }}</p>

        @if ($takeaway['strength'])
            <div class="box green"><strong>1 · {{ __('takeaway.strength') }} · {{ $takeaway['strength']['name'] }}</strong><br>
                {{ $takeaway['strength']['situations'] !== null ? __('takeaway.strength_situations', ['score' => $whole($takeaway['strength']['situations']), 'count' => $takeaway['strength']['situation_count']]) : __('takeaway.strength_start', ['score' => $whole($takeaway['strength']['start'])]) }}</div>
        @endif
        @if ($takeaway['own_value'])
            <div class="box charcoal"><strong>2 · {{ __('takeaway.own_value') }} · {{ $takeaway['own_value']['name'] }}</strong><br>{{ __('takeaway.own_value_line', ['count' => $takeaway['own_value']['team_count']]) }}</div>
        @endif
        @if ($takeaway['self_gap'])
            <div class="box coral"><strong>3 · {{ __('takeaway.self_gap') }} · {{ $takeaway['self_gap']['name'] }}</strong><br>{{ __('takeaway.self_gap_line', ['start' => $whole($takeaway['self_gap']['start']), 'situations' => $whole($takeaway['self_gap']['situations'])]) }}</div>
        @endif
        @if ($takeaway['others_gap'])
            <div class="box yellow"><strong>4 · {{ __('takeaway.others_gap') }} · {{ $takeaway['others_gap']['name'] }}</strong><br>{{ __('takeaway.others_gap_line', ['you' => $whole($takeaway['others_gap']['start']), 'others' => $whole($takeaway['others_gap']['others'])]) }}</div>
        @endif

        <h2>{{ __('takeaway.scale') }}</h2>
        <table>
            <tr><th></th><th class="num">{{ __('takeaway.legend.start') }}</th><th class="num">{{ __('takeaway.legend.situations') }}</th><th class="num">{{ __('takeaway.legend.others') }}</th></tr>
            @foreach ($takeaway['rows'] as $row)
                <tr><td>{{ $row['name'] }}</td><td class="num">{{ $whole($row['start']) }}</td><td class="num">{{ $whole($row['situations']) }}</td><td class="num">{{ $whole($row['others']) }}</td></tr>
            @endforeach
        </table>

        @if ($recommendation)
            <div class="box yellow"><strong>{{ __('takeaway.recommendation') }}</strong><br>{{ $recommendation['text'] }}</div>
        @endif
    </section>
</x-pdf-layout>
