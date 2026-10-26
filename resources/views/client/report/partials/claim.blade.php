{{--
    An approved claim (27 · 27.1, prototype .lbl .stm .stl): "Repedés · Nyíltság",
    the claim, the marker with the evidence link, then its first quote.
--}}
<div class="flex flex-col gap-1">
    <p class="text-xs font-semibold tracking-[.08em] text-green uppercase">{{ \App\Services\Report\ReportText::claimKind($claim['kind'], $report['meta']) }}@if (filled($claimRow ?? null)) · {{ $claimRow }}@endif</p>
    <p class="text-lg leading-snug font-semibold [text-wrap:pretty]">{{ $claim['text'] }}</p>
    @if ($claim['kind'] === 'hunch')
        <p class="text-sm text-grey-700">{{ trans_choice('report.hunch_traces', count($claim['traces']), ['count' => count($claim['traces'])]) }}</p>
    @endif
    <p class="text-xs text-grey-700">
        <span class="font-semibold text-green">{{ __('report.claim_marker_short') }}</span> · {{ __('report.claim_reviewed') }}
        @isset($evidenceUrl)
            · <a href="{{ $evidenceUrl }}" class="font-semibold text-charcoal">{{ __('report.evidence_link') }} <x-megis.icon name="chevron-right" class="inline size-3 align-middle" /></a>
        @endisset
    </p>
    @if (($claim['traces'][0] ?? null) && ($withQuote ?? true))
        <div class="mt-1">
            @include('client.report.partials.quote', ['trace' => $claim['traces'][0]])
        </div>
    @endif
</div>
