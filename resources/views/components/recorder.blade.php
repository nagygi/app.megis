{{--
    The browser recorder of the Értéktükör and of "Egy helyzet" (06 · 6.1, 12 · 12.3):
    a timer with a filling ring, a level meter that shows the microphone hears
    the speaker, encouraging lines, a supporting question after silence; a pause
    and "Befejeztem". The Livewire component has a "recording" upload and submitRecording(seconds).
    Without the inline submit the page's own button (x-recorder-submit) sends it:
    the recorder reports its state ("recorder-state") and sends on "recorder-submit".
    "check" names the component's method that checks the rest before the upload.
--}}
@props(['questions' => [], 'check' => null, 'inlineSubmit' => true])

@php
    $recorderConfig = [
        'max' => (int) config('megis.materials.recording_max_seconds'),
        'warning' => (int) config('megis.materials.recording_warning_seconds'),
        'recommended' => (int) config('megis.materials.recording_recommended_seconds'),
        'silence' => (int) config('megis.materials.silence_seconds'),
        'questions' => $questions,
        'check' => $check,
        'texts' => [
            'start' => __('mirror.motivation.start'),
            'oneMinute' => __('mirror.motivation.one_minute'),
            'threeMinutes' => __('mirror.motivation.three_minutes'),
            'denied' => __('mirror.mic_denied'),
            'maxReached' => __('mirror.max_reached'),
            'uploadFailed' => __('materials.unreadable'),
        ],
    ];
    $circumference = 2 * M_PI * 54;
@endphp

    {{-- @js is not compiled in a component's attribute, Js::from in an echo is --}}
    <x-ui.card as="section" padding="lg" x-data="mirrorRecorder({{ \Illuminate\Support\Js::from($recorderConfig) }})" x-on:recorder-submit.window="submit()" class="flex flex-col items-center gap-5" aria-live="polite">
        <template x-if="! supported">
            <p class="text-center">{{ __('mirror.unsupported') }}</p>
        </template>

        <div x-show="supported" class="flex flex-col items-center gap-5">
            <div class="relative size-36">
                <svg viewBox="0 0 120 120" class="size-36 -rotate-90" aria-hidden="true">
                    <circle cx="60" cy="60" r="54" fill="none" stroke-width="8" class="stroke-grey-200" />
                    <circle
                        cx="60" cy="60" r="54" fill="none" stroke-width="8" stroke-linecap="round"
                        class="stroke-coral transition-[stroke-dashoffset] duration-1000"
                        stroke-dasharray="{{ $circumference }}"
                        :stroke-dashoffset="{{ $circumference }} * (1 - progress)"
                    />
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-3xl font-semibold tabular-nums" x-text="timeLabel">0:00</span>
            </div>

            {{-- The level meter: it moves with the voice, so the speaker sees they are heard --}}
            <div x-show="state === 'recording' || state === 'paused'" x-cloak class="flex items-center gap-3" data-level-meter>
                <span class="flex h-6 items-center gap-1" aria-hidden="true">
                    <template x-for="index in [0, 1, 2, 3, 4]" :key="index">
                        <span
                            class="h-6 w-1.5 origin-center rounded-pill transition-transform duration-75"
                            :class="state === 'paused' ? 'bg-grey-350' : 'bg-coral'"
                            :style="`transform: scaleY(${state === 'paused' ? 0.15 : bar(index)})`"
                        ></span>
                    </template>
                </span>
                <span class="text-sm font-semibold" :class="state === 'paused' ? 'text-grey-700' : 'text-coral-ink'" x-text="state === 'paused' ? @js(__('mirror.paused_short')) : @js(__('mirror.hearing'))"></span>
            </div>

            <p x-show="state === 'recording'" class="text-lg font-semibold" x-text="motivation"></p>
            <p x-show="state === 'paused'" x-cloak class="text-center text-grey-700">{{ __('mirror.paused') }}</p>
            <p x-show="(state === 'recording' || state === 'paused') && seconds >= {{ $recorderConfig['warning'] }}" class="text-sm font-semibold" role="alert">{{ __('mirror.warning') }}</p>

            <p
                x-show="state === 'recording' && question"
                x-text="question"
                class="rounded-field bg-yellow-pale px-4 py-2 text-center transition-opacity duration-500"
                :class="speaking ? 'opacity-50' : 'opacity-100'"
            ></p>

            <p x-show="notice" x-text="notice" class="text-sm"></p>
            <p x-show="error" x-text="error" class="text-sm font-semibold" role="alert"></p>
            <p x-show="state === 'recorded' && isShort" class="text-center text-sm text-grey-700">{{ __('mirror.short') }}</p>

            <div class="flex flex-wrap justify-center gap-3">
                <x-ui.button size="lg" icon="mic" x-show="state === 'idle'" x-on:click="start()">{{ __('mirror.record_start') }}</x-ui.button>
                <x-ui.button size="lg" tone="outline" x-show="state === 'recording' && canPause" x-on:click="pause()">{{ __('mirror.record_pause') }}</x-ui.button>
                <x-ui.button size="lg" tone="outline" icon="mic" x-show="state === 'paused'" x-cloak x-on:click="resume()">{{ __('mirror.record_resume') }}</x-ui.button>
                <x-ui.button size="lg" x-show="state === 'recording' || state === 'paused'" x-on:click="stop()">{{ __('mirror.record_stop') }}</x-ui.button>
                @if ($inlineSubmit)
                    <x-ui.button size="lg" x-show="state === 'recorded'" x-on:click="submit()">{{ __('mirror.submit') }}</x-ui.button>
                @endif
                <x-ui.button size="lg" tone="outline" x-show="state === 'recorded'" x-on:click="restart()">{{ __('mirror.restart') }}</x-ui.button>
                <p x-show="state === 'uploading'" class="font-semibold">{{ __('mirror.uploading') }} <span x-text="uploadProgress + '%'"></span></p>
            </div>
        </div>

        <x-form.error name="recording" id="recording" />
    </x-ui.card>
