{{--
    "hamarosan": a block of the prototype whose page or data is not built yet
    (Brand riport, Képesség, Coach, Bridge to Purpose). A dashed pill that
    keeps AA contrast on cream, light yellow and white; dark on charcoal.
--}}
@props(['dark' => false])

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center rounded-pill border border-dashed px-2 py-0.5 text-xs font-semibold tracking-normal normal-case',
    'border-grey-600 text-grey-700' => ! $dark,
    'border-grey-400 text-grey-200' => $dark,
]) }}>{{ __('report.soon') }}</span>
