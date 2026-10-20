{{-- Egyén · Csapat · Szervezet (27 · 27.9); the team view is grey below the threshold (18 · 18.6), saying why. --}}
<div class="flex flex-wrap items-center gap-x-3 gap-y-2">
    <x-ui.segmented :label="__('report.views_label')">
        @foreach (['egyen', 'csapat', 'szervezet'] as $option)
            <x-ui.segment
                :selected="$mode === $option"
                :href="request()->fullUrlWithQuery(['nezet' => $option])"
                :disabled-reason="$mode !== $option && $option === 'csapat' && ! $report['meta']['shows_experience'] ? __('report.team_disabled') : null"
            >{{ __('report.views.'.$option) }}</x-ui.segment>
        @endforeach
    </x-ui.segmented>
    @if ($mode === 'egyen')
        <span class="text-sm text-grey-700">{{ __('report.from_your_materials') }}</span>
    @elseif ($mode === 'szervezet')
        <span class="text-sm text-grey-700">{{ __('report.organization_view') }}</span>
    @endif
</div>
