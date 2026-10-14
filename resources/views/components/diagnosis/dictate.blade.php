{{--
    Microphone next to a text field (08 · 8.1, 10 · 10.1, 24). The Livewire
    component uses WithDictation; "pending" is true while the transcript is on its way.
--}}
@props(['field', 'label', 'pending' => false])

<div
    x-data="dictation({
        field: @js($field),
        max: {{ (int) config('megis.materials.dictation_max_seconds') }},
        texts: { denied: @js(__('diagnosis.dictation.denied')), failed: @js(__('diagnosis.dictation.failed')) },
    })"
    {{ $attributes->class(['flex flex-wrap items-center gap-2 text-sm']) }}
>
    <template x-if="supported">
        <button
            type="button"
            @click="toggle()"
            :aria-pressed="recording"
            :disabled="uploading || {{ $pending ? 'true' : 'false' }}"
            class="press inline-flex items-center gap-1.5 rounded-pill border border-charcoal px-3 py-1 font-semibold disabled:cursor-wait disabled:opacity-60"
            :class="recording && 'bg-coral text-black border-coral'"
        >
            <svg viewBox="0 0 24 24" class="size-4" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="3" width="6" height="11" rx="3" />
                <path d="M5 11a7 7 0 0 0 14 0M12 18v3" />
            </svg>
            <span x-text="recording ? @js(__('diagnosis.dictation.stop')) : @js(__('diagnosis.dictation.start'))"></span>
            <span class="sr-only">· {{ $label }}</span>
        </button>
    </template>

    <span x-show="recording" x-text="timeLabel" class="tabular-nums" aria-live="off"></span>
    <span x-show="uploading" x-cloak role="status">{{ __('diagnosis.dictation.uploading') }}</span>
    @if ($pending)
        <span role="status">{{ __('diagnosis.dictation.transcribing') }}</span>
    @endif
    <span x-show="error" x-text="error" x-cloak class="text-coral-dark" role="alert"></span>
</div>
