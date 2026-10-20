{{--
    The "Tovább" of a step page, the same everywhere: dark, large, with the
    arrow; "Mentve" right of it on the autosaving steps; when it cannot go on
    yet, the reason under it instead of a silent grey button. After a click
    that fails validation the first invalid field takes the focus
    (data-focus-errors, resources/js/app.js). The slot renames it.
--}}
@props(['disabledReason' => null, 'saved' => true])

<div class="flex flex-col gap-2">
    <div class="flex flex-wrap items-center gap-4">
        <x-ui.button size="lg" icon-right="arrow-right" data-focus-errors :disabled="filled($disabledReason)" {{ $attributes }}>
            {{ (string) $slot !== '' ? $slot : __('diagnosis.next') }}
        </x-ui.button>
        @if ($saved)
            <x-diagnosis.saved />
        @endif
    </div>
    @if (filled($disabledReason))
        <p class="text-sm text-grey-700">{{ $disabledReason }}</p>
    @endif
</div>
