{{-- ET2 · the recording (06 · 6.1). One question, three ways to answer. --}}
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

<div class="mx-auto flex max-w-xl flex-col gap-6 py-4">
    <div class="flex flex-col gap-3">
        @if ($isPublic)
            <h1 class="text-3xl font-extrabold uppercase">{{ __('mirror.title') }}</h1>
        @endif
        <p class="text-xl">{{ __('mirror.prompt') }}</p>
        <p class="text-grey-700">{{ __('mirror.hint') }}</p>
    </div>

    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('mirror.title') }}">
        @foreach (['record' => __('mirror.mode_record'), 'text' => __('mirror.mode_text'), 'file' => __('mirror.mode_file')] as $key => $label)
            <button
                type="button"
                wire:click="setMode('{{ $key }}')"
                @class([
                    'rounded-pill border-[1.5px] px-4 py-1.5 text-sm font-semibold',
                    'border-charcoal bg-charcoal text-cream' => $mode === $key,
                    'border-charcoal text-charcoal hover:bg-charcoal/5' => $mode !== $key,
                ])
                aria-pressed="{{ $mode === $key ? 'true' : 'false' }}"
            >{{ $label }}</button>
        @endforeach
    </div>

    @if ($mode === 'record')
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
    @elseif ($mode === 'text')
        <form wire:submit="submitText" class="flex flex-col gap-4">
            <x-form.textarea name="text" wire:model="text" rows="10" :label="__('mirror.text_label')" :help="__('mirror.text_help')" maxlength="{{ config('megis.materials.text_max_characters') }}" required />
            <div>
                <x-ui.button type="submit" size="lg">{{ __('mirror.submit') }}</x-ui.button>
            </div>
        </form>
    @else
        <form wire:submit="submitFile" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="mirror-file" class="text-sm font-medium">{{ __('mirror.file_label') }} <span class="font-normal text-grey-600">· {{ __('ui.required') }}</span></label>
                <input id="mirror-file" type="file" wire:model="file" accept=".{{ implode(',.', config('megis.materials.text_extensions')) }}" aria-describedby="mirror-file-help" class="text-base">
                <p id="mirror-file-help" class="text-sm text-grey-600">{{ __('mirror.file_help', $textRules) }}</p>
                <x-form.error name="file" id="file" />
            </div>
            <div>
                <x-ui.button type="submit" size="lg" wire:loading.attr="disabled" wire:target="file">{{ __('mirror.submit') }}</x-ui.button>
            </div>
        </form>
    @endif

    @if ($isPublic)
        <x-turnstile model="turnstileToken" />
    @endif
</div>
