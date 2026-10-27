<span class="flex shrink-0 flex-col items-center" aria-hidden="true">
    @if ($state->done)
        <span class="grid size-5.5 place-items-center rounded-pill bg-green text-cream"><x-megis.icon name="check" class="size-3" /></span>
    @elseif ($current)
        <span class="grid size-5.5 place-items-center rounded-pill border-2 border-coral"><span class="size-2.5 rounded-pill bg-coral"></span></span>
    @else
        <span class="size-5.5 rounded-pill border-[1.5px] border-grey-500"></span>
    @endif
    @unless ($last)
        <span class="mt-[3px] min-h-3.5 w-0.5 flex-1 bg-grey-250"></span>
    @endunless
</span>
<span @class(['flex min-w-0 flex-1 items-baseline justify-between gap-3 pt-0.5', 'min-h-11 pb-3.5' => ! $last])>
    <span class="min-w-0">
        <span @class(['block font-semibold underline-offset-3', 'group-hover:underline' => $href])>{{ $state->step->label() }}</span>
        @if ($state->readonly)
            <span class="block text-sm text-grey-700">{{ __('diagnosis.readonly') }}</span>
        @endif
    </span>
    <span class="shrink-0 whitespace-nowrap text-sm text-grey-700">{{ __('diagnosis.minutes', ['minutes' => $state->minutes]) }}</span>
    <span class="sr-only">· {{ $status }}</span>
</span>
