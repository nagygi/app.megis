{{-- Egyén · Csapat · Szervezet (27 · 27.9); the team view is grey below the threshold (18 · 18.6). --}}
<div class="flex flex-wrap items-center gap-2 text-sm" role="group" aria-label="{{ __('report.views_label') }}">
    @foreach (['egyen', 'csapat', 'szervezet'] as $option)
        @php($disabled = $option === 'csapat' && ! $report['meta']['shows_experience'])
        @if ($mode === $option)
            <span class="rounded-pill bg-charcoal px-3 py-1 font-semibold text-cream" aria-current="true">{{ __('report.views.'.$option) }}</span>
        @elseif ($disabled)
            <span class="rounded-pill border border-grey-300 px-3 py-1 text-grey-600" aria-disabled="true" title="{{ __('report.team_disabled') }}">{{ __('report.views.'.$option) }}</span>
        @else
            <a href="{{ request()->fullUrlWithQuery(['nezet' => $option]) }}" class="rounded-pill border border-charcoal px-3 py-1 font-semibold text-charcoal no-underline">{{ __('report.views.'.$option) }}</a>
        @endif
    @endforeach
    @if ($mode === 'egyen')
        <span class="text-grey-700">{{ __('report.from_your_materials') }}</span>
    @elseif ($mode === 'szervezet')
        <span class="text-grey-700">{{ __('report.organization_view') }}</span>
    @endif
</div>
