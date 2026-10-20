{{-- RM · Melléklet: honnan tudjuk (28 · 28.2). The trust page: what the mégis wrote over shows too. --}}
@use('App\Services\Report\ReportText')
@php
    $appendix = $report['appendix'];
    $date = fn (?string $value) => $value === null ? '–' : \Illuminate\Support\Carbon::parse($value)->format('Y.m.d.');
@endphp
<x-layouts::client :title="__('appendix.title')" color="white" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$url('show')" :back-label="__('report.title')">
    <div class="flex max-w-2xl flex-col gap-6">
        <x-ui.card as="dl" class="grid gap-3 sm:grid-cols-[8rem_1fr]">
            <dt class="font-semibold">{{ __('appendix.who') }}</dt>
            <dd>{{ $report['meta']['has_peers']
                ? __('appendix.who_text', ['leader' => $appendix['who']['leader'], 'subject' => $appendix['who']['subject'], 'peers' => $appendix['who']['peers'], 'answered' => $appendix['who']['answered']])
                : __('appendix.who_alone', ['leader' => $appendix['who']['leader']]) }}</dd>
            <dt class="font-semibold">{{ __('appendix.from') }}</dt>
            <dd>
                {{ __('appendix.from_text', $appendix['sources']) }}
                @if ($appendix['sources']['calendar'])
                    · {{ __('appendix.calendar') }}
                @endif
            </dd>
            <dt class="font-semibold">{{ __('appendix.when') }}</dt>
            <dd>{{ $date($appendix['window']['from']) }} – {{ $date($appendix['window']['to']) }}</dd>
            <dt class="font-semibold">{{ __('appendix.read_by') }}</dt>
            <dd>{{ __('appendix.read_by_text', ['date' => $date($appendix['read_by']['published_on'])]) }}</dd>
        </x-ui.card>

        <x-ui.disclosure open :summary="__('appendix.quotes', ['count' => count($appendix['quotes'])])">
            <div class="flex flex-col gap-2">
                @forelse ($appendix['quotes'] as $trace)
                    @include('client.report.partials.quote', ['trace' => $trace])
                @empty
                    <p class="text-grey-700">–</p>
                @endforelse
            </div>
        </x-ui.disclosure>

        <x-ui.disclosure :summary="__('appendix.claims', $appendix['claims']['counts'])">
            <ul class="flex flex-col gap-2 text-sm">
                @foreach ($appendix['claims']['list'] as $claim)
                    <li>
                        <span class="font-semibold">{{ ReportText::claimKind($claim['kind'], $report['meta']) }}</span> · {{ $claim['text'] }}
                        <span class="block text-xs text-grey-700">{{ $claim['row'] }} · {{ trans_choice('report.evidence', $claim['traces'], ['count' => $claim['traces']]) }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.disclosure>

        <x-ui.disclosure :summary="__('appendix.rows', ['count' => count($appendix['rows'])])">
            <ul class="flex flex-col gap-1 text-sm">
                @foreach ($appendix['rows'] as $row)
                    <li class="flex justify-between gap-3">
                        <span>{{ $row['label'] }}</span>
                        <span class="tabular-nums">{{ $row['score_state'] === 'scored' ? ReportText::decimal($row['score']) : ($row['score_state'] === 'insufficient_data' ? __('report.insufficient') : '–') }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.disclosure>

        <x-ui.card as="section" class="flex flex-col gap-2" aria-labelledby="overrides-title">
            <h2 id="overrides-title" class="font-semibold">{{ __('appendix.overrides') }}</h2>
            @forelse ($appendix['overrides'] as $override)
                <div class="flex flex-col gap-0.5 border-t border-grey-200 pt-2 text-sm first:border-t-0 first:pt-0">
                    <p class="flex flex-wrap items-center gap-1 font-semibold">{{ $override['label'] }} · {{ ReportText::decimal($override['from']) }} <x-megis.icon name="arrow-right" :label="__('appendix.changed_to')" /> {{ ReportText::decimal($override['to']) }}</p>
                    <p>{{ $override['reason'] }}</p>
                    <p class="text-xs text-grey-700">{{ __('appendix.override_by', ['date' => $date($override['date'])]) }}</p>
                </div>
            @empty
                <p class="text-sm text-grey-700">{{ __('appendix.no_overrides') }}</p>
            @endforelse
        </x-ui.card>
    </div>
</x-layouts::client>
