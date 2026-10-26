{{-- RM · Melléklet: honnan tudjuk (28 · 28.2, prototype RM). The trust page: what the mégis wrote over shows too. --}}
@use('App\Services\Report\ReportText')
@php
    $appendix = $report['appendix'];
    $date = fn (?string $value) => $value === null ? '–' : \Illuminate\Support\Carbon::parse($value)->format('Y.m.d.');
    $standalone = $report['meta']['standalone'] ?? false;
@endphp
<x-layouts::client
    :title="__('appendix.pdf_title')"
    color="light-yellow"
    symbol="circle"
    :label="__('appendix.pdf_title')"
    :subtitle="__('report.appendix_page.question')"
    :back="$url('show')"
    :back-label="__('report.pages.answer')"
>
    <div class="flex max-w-2xl flex-col gap-5">
        <dl class="grid grid-cols-[6rem_1fr] gap-0.5 text-sm leading-relaxed">
            <dt class="px-0 py-2 font-semibold text-green">{{ __('appendix.who') }}</dt>
            <dd class="bg-white px-3 py-2">{{ $report['meta']['has_peers']
                ? __('appendix.who_text', ['leader' => $appendix['who']['leader'], 'subject' => $appendix['who']['subject'], 'peers' => $appendix['who']['peers'], 'answered' => $appendix['who']['answered']])
                : __('appendix.who_alone', ['leader' => $appendix['who']['leader']]) }}</dd>
            <dt class="py-2 font-semibold text-green">{{ __('appendix.from') }}</dt>
            <dd class="bg-white px-3 py-2">
                @if ($standalone)
                    {{ ReportText::standaloneSource($appendix['source']) }}
                @else
                    {{ __('appendix.from_text', $appendix['sources']) }}
                    @if ($appendix['sources']['calendar'])
                        · {{ __('appendix.calendar') }}
                    @endif
                @endif
            </dd>
            <dt class="py-2 font-semibold text-green">{{ __('appendix.when') }}</dt>
            <dd class="bg-white px-3 py-2">{{ $date($appendix['window']['from']) }} – {{ $date($appendix['window']['to']) }}</dd>
            <dt class="py-2 font-semibold text-green">{{ __('appendix.read_by') }}</dt>
            <dd class="bg-white px-3 py-2">{{ __('appendix.read_by_text', ['date' => $date($appendix['read_by']['published_on'])]) }}</dd>
        </dl>

        <div class="flex flex-col">
            <details open class="group border-b border-grey-200">
                <summary class="press flex cursor-pointer list-none items-center gap-2.5 py-3 [&::-webkit-details-marker]:hidden">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ __('appendix.quotes_title') }}</span>
                        <span class="block text-xs text-grey-700">{{ __('appendix.quotes_help') }}</span>
                    </span>
                    <span class="text-sm font-semibold tabular-nums">{{ count($appendix['quotes']) }}</span>
                    <x-megis.icon name="chevron-down" class="shrink-0 text-grey-600 transition-transform group-open:rotate-180" />
                </summary>
                <div class="flex flex-col gap-2 pb-3">
                    @forelse ($appendix['quotes'] as $trace)
                        @include('client.report.partials.quote', ['trace' => $trace])
                    @empty
                        <p class="text-sm text-grey-700">–</p>
                    @endforelse
                </div>
            </details>

            {{-- A standalone diagnosis has no reading, so no claims (04 · 4.6). --}}
            @unless ($standalone)
                <details class="group border-b border-grey-200">
                    <summary class="press flex cursor-pointer list-none items-center gap-2.5 py-3 [&::-webkit-details-marker]:hidden">
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ __('appendix.claims_title') }}</span>
                            <span class="block text-xs text-grey-700">{{ __('appendix.claims_help', $appendix['claims']['counts']) }}</span>
                        </span>
                        <span class="text-sm font-semibold tabular-nums">{{ count($appendix['claims']['list']) }}</span>
                        <x-megis.icon name="chevron-down" class="shrink-0 text-grey-600 transition-transform group-open:rotate-180" />
                    </summary>
                    <div class="flex flex-col pb-3">
                        @foreach ($appendix['claims']['list'] as $claim)
                            <x-report.row :title="$claim['text']" :meta="ReportText::claimKind($claim['kind'], $report['meta']).' · '.$claim['row'].' · '.trans_choice('report.evidence', $claim['traces'], ['count' => $claim['traces']])" :bar="false" :number="$claim['traces']" />
                        @endforeach
                    </div>
                </details>
            @endunless

            <details class="group border-b border-grey-200">
                <summary class="press flex cursor-pointer list-none items-center gap-2.5 py-3 [&::-webkit-details-marker]:hidden">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ __('appendix.rows_title') }}</span>
                        <span class="block text-xs text-grey-700">{{ __('report.menu.internal_meta') }}</span>
                    </span>
                    <span class="text-sm font-semibold tabular-nums">{{ count($appendix['rows']) }}</span>
                    <x-megis.icon name="chevron-down" class="shrink-0 text-grey-600 transition-transform group-open:rotate-180" />
                </summary>
                <div class="flex flex-col pb-3">
                    @foreach ($appendix['rows'] as $row)
                        <x-report.row
                            :title="$row['label']"
                            :meta="__('appendix.kinds.'.$row['kind'])"
                            :bar="false"
                            :number="$row['score_state'] === 'scored' ? ReportText::decimal($row['score']) : ($row['score_state'] === 'insufficient_data' ? __('report.insufficient') : '–')"
                        />
                    @endforeach
                </div>
            </details>

            <details class="group border-b border-grey-200">
                <summary class="press flex cursor-pointer list-none items-center gap-2.5 py-3 [&::-webkit-details-marker]:hidden">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ __('appendix.overrides') }}</span>
                        <span class="block text-xs text-grey-700">{{ $appendix['overrides'] === [] ? __('appendix.no_overrides') : __('appendix.overrides_help') }}</span>
                    </span>
                    <span class="text-sm font-semibold tabular-nums">{{ count($appendix['overrides']) }}</span>
                    <x-megis.icon name="chevron-down" class="shrink-0 text-grey-600 transition-transform group-open:rotate-180" />
                </summary>
                <div class="flex flex-col gap-2 pb-3">
                    @forelse ($appendix['overrides'] as $override)
                        <div class="flex flex-col gap-0.5 border-t border-grey-200 pt-2 text-sm first:border-t-0 first:pt-0">
                            <p class="flex flex-wrap items-center gap-1 font-semibold">{{ $override['label'] }} · {{ ReportText::decimal($override['from']) }} <x-megis.icon name="arrow-right" :label="__('appendix.changed_to')" /> {{ ReportText::decimal($override['to']) }}</p>
                            <p>{{ $override['reason'] }}</p>
                            <p class="text-xs text-grey-700">{{ __('appendix.override_by', ['date' => $date($override['date'])]) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-grey-700">{{ __('appendix.no_overrides') }}</p>
                    @endforelse
                </div>
            </details>
        </div>

        @if (! $preview && config('megis.pdf_downloads'))
            <x-ui.button size="sm" icon="document" class="self-start" :href="$url('pdf')">{{ __('report.closing.pdf') }}</x-ui.button>
        @endif
    </div>
</x-layouts::client>
