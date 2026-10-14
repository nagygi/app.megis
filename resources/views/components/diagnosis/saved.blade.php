{{--
    "Mentve" after an automatic save, or the failure line (15 · 15.8). The
    component dispatches "saved" or "save-failed".
--}}
<div
    x-data="{ state: null, timer: null }"
    x-on:saved.window="state = 'saved'; clearTimeout(timer); timer = setTimeout(() => state = null, 2500)"
    x-on:save-failed.window="state = 'failed'; clearTimeout(timer)"
    {{ $attributes->class(['min-h-6 text-sm']) }}
    role="status"
    aria-live="polite"
>
    <span x-show="state === 'saved'" x-cloak class="inline-flex items-center gap-1 font-semibold text-green">
        <x-megis.icon name="check" class="size-4" /> {{ __('diagnosis.saved') }}
    </span>
    <span x-show="state === 'failed'" x-cloak class="font-semibold text-coral-ink">{{ __('diagnosis.save_failed') }}</span>
</div>
