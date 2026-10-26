{{--
    Egyén · Csapat · Szervezet (27 · 27.9, prototype R1·b): one slim pill
    on the right, "összevetés ›" on the left; the team view is grey below the
    threshold (18 · 18.6), saying why.
--}}
<div class="flex flex-col gap-1.5">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ $url('compare') }}" class="text-sm whitespace-nowrap text-grey-700 no-underline hover:underline">{{ mb_strtolower(__('report.compare_link')) }} <x-megis.icon name="chevron-right" class="inline size-3.5 align-middle" /></a>
        <nav class="flex shrink-0 overflow-hidden rounded-pill border border-charcoal" aria-label="{{ __('report.views_label') }}">
            @foreach (['egyen', 'csapat', 'szervezet'] as $option)
                @php($disabled = $mode !== $option && $option === 'csapat' && ! $report['meta']['shows_experience'])
                @if ($mode === $option)
                    <span aria-current="page" class="bg-charcoal px-3 py-1.5 text-xs font-semibold text-cream">{{ __('report.views.'.$option) }}</span>
                @elseif ($disabled)
                    <span aria-disabled="true" title="{{ __('report.team_disabled') }}" class="cursor-not-allowed px-3 py-1.5 text-xs text-grey-600">{{ __('report.views.'.$option) }}<span class="sr-only"> ({{ __('report.team_disabled') }})</span></span>
                @else
                    <a href="{{ request()->fullUrlWithQuery(['nezet' => $option]) }}" @class(['px-3 py-1.5 text-xs text-charcoal no-underline hover:bg-yellow-pale', 'border-l border-charcoal' => $option === 'szervezet'])>{{ __('report.views.'.$option) }}</a>
                @endif
            @endforeach
        </nav>
    </div>
    @if ($mode === 'egyen')
        <span class="text-right text-sm text-grey-700">{{ __('report.from_your_materials') }}</span>
    @elseif ($mode === 'szervezet')
        <span class="text-right text-sm text-grey-700">{{ __('report.organization_view') }}</span>
    @endif
</div>
