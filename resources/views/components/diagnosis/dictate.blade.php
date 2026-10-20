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
            class="press inline-flex min-h-10 items-center gap-2 rounded-pill border-[1.5px] border-charcoal px-4 font-semibold hover:bg-yellow-pale disabled:cursor-wait disabled:border-grey-300 disabled:text-grey-600"
            :class="recording && 'border-coral bg-coral text-black hover:bg-coral-light'"
        >
            <x-megis.icon name="mic" />
            <span x-text="recording ? @js(__('diagnosis.dictation.stop')) : @js(__('diagnosis.dictation.start'))"></span>
            <span class="sr-only">· {{ $label }}</span>
        </button>
    </template>

    <span x-show="recording" x-text="timeLabel" class="tabular-nums" aria-live="off"></span>
    <span x-show="uploading" x-cloak role="status">{{ __('diagnosis.dictation.uploading') }}</span>
    @if ($pending)
        <span role="status">{{ __('diagnosis.dictation.transcribing') }}</span>
    @endif
    <span x-show="error" x-text="error" x-cloak class="text-coral-ink" role="alert"></span>
</div>
