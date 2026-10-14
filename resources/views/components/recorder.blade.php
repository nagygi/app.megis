{{--
    The browser recorder of the Értéktükör and of "Egy helyzet" (06 · 6.1, 12 · 12.3):
    a timer with a filling ring, encouraging lines, a supporting question after
    silence. The Livewire component has a "recording" upload and submitRecording(seconds).
--}}
@props(['questions' => []])

@php
    $recorderConfig = [
        'max' => (int) config('megis.materials.recording_max_seconds'),
        'warning' => (int) config('megis.materials.recording_warning_seconds'),
        'recommended' => (int) config('megis.materials.recording_recommended_seconds'),
        'silence' => (int) config('megis.materials.silence_seconds'),
        'questions' => $questions,
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

    <section x-data="mirrorRecorder(@js($recorderConfig))" class="flex flex-col items-center gap-5 rounded-card bg-white p-6 shadow-sm" aria-live="polite">
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

            <p x-show="state === 'recording'" class="text-lg font-semibold" x-text="motivation"></p>
            <p x-show="state === 'recording' && seconds >= {{ $recorderConfig['warning'] }}" class="text-sm font-semibold" role="alert">{{ __('mirror.warning') }}</p>

            <p
                x-show="state === 'recording' && question"
                x-text="question"
                class="rounded-xl bg-yellow-pale px-4 py-2 text-center transition-opacity duration-500"
                :class="speaking ? 'opacity-50' : 'opacity-100'"
            ></p>

            <p x-show="notice" x-text="notice" class="text-sm"></p>
            <p x-show="error" x-text="error" class="text-sm font-semibold" role="alert"></p>
            <p x-show="state === 'recorded' && isShort" class="text-center text-sm text-grey-700">{{ __('mirror.short') }}</p>

            <div class="flex flex-wrap justify-center gap-3">
                <x-ui.button size="lg" x-show="state === 'idle'" x-on:click="start()">{{ __('mirror.record_start') }}</x-ui.button>
                <x-ui.button size="lg" x-show="state === 'recording'" x-on:click="stop()">{{ __('mirror.record_stop') }}</x-ui.button>
                <x-ui.button size="lg" x-show="state === 'recorded'" x-on:click="submit()">{{ __('mirror.submit') }}</x-ui.button>
                <x-ui.button size="lg" tone="outline" x-show="state === 'recorded'" x-on:click="restart()">{{ __('mirror.restart') }}</x-ui.button>
                <p x-show="state === 'uploading'" class="font-semibold">{{ __('mirror.uploading') }} <span x-text="uploadProgress + '%'"></span></p>
            </div>
        </div>

        <x-form.error name="recording" id="recording" />
    </section>
