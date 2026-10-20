{{-- An approved claim with its first quote and the marker (27 · 27.1). --}}
<div class="flex flex-col gap-2">
    <p>
        <span class="text-xs font-semibold uppercase tracking-wide text-grey-700">{{ \App\Services\Report\ReportText::claimKind($claim['kind'], $report['meta']) }}</span>
        <span class="block">{{ $claim['text'] }}</span>
        @if ($claim['kind'] === 'hunch')
            <span class="block text-sm text-grey-700">{{ trans_choice('report.hunch_traces', count($claim['traces']), ['count' => count($claim['traces'])]) }}</span>
        @endif
    </p>
    @if (($claim['traces'][0] ?? null) && ($withQuote ?? true))
        @include('client.report.partials.quote', ['trace' => $claim['traces'][0]])
    @endif
    <p class="text-xs text-grey-700">
        {{ __('report.claim_marker') }}
        @isset($evidenceUrl)
            · <a href="{{ $evidenceUrl }}" class="inline-flex items-center gap-0.5 font-semibold">{{ __('report.evidence_link') }} <x-megis.icon name="chevron-right" /></a>
        @endisset
    </p>
</div>
