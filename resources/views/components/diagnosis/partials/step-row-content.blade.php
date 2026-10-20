<span @class([
    'grid size-7 shrink-0 place-items-center rounded-pill text-xs font-extrabold',
    'bg-coral text-black' => $current,
    'bg-green text-cream' => $state->done,
    'bg-grey-300 text-grey-700' => ! $state->done && ! $current,
]) aria-hidden="true">
    @if ($state->done)
        <x-megis.icon name="check" class="size-4" />
    @else
        {{ $number }}
    @endif
</span>
<span class="flex-1">
    <span class="block font-semibold">{{ $state->step->label() }}</span>
    @if ($state->readonly)
        <span class="block text-sm text-grey-700">{{ __('diagnosis.readonly') }}</span>
    @endif
</span>
<span class="text-sm text-grey-700">{{ __('diagnosis.minutes', ['minutes' => $state->minutes]) }}</span>
<span class="sr-only">· {{ $status }}</span>
