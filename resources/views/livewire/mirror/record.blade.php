{{-- ET2 · the recording (06 · 6.1). One question, three ways to answer. --}}
<div class="mx-auto flex max-w-xl flex-col gap-6 py-4">
    <div class="flex flex-col gap-3">
        @if ($isPublic)
            <h1 class="text-3xl font-extrabold uppercase">{{ __('mirror.title') }}</h1>
        @endif
        <p class="text-xl">{{ __('mirror.prompt') }}</p>
        <p class="text-grey-700">{{ __('mirror.hint') }}</p>
    </div>

    @if ($diagnosisOptions->count() > 1)
        {{-- 17 · 17.6: more running diagnoses --}}
        <fieldset class="flex flex-col gap-2 rounded-card bg-white p-5">
            <legend class="mb-1 font-semibold">{{ __('mirror.which_diagnosis') }}</legend>
            @foreach ($diagnosisOptions as $option)
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="mainDiagnosisId" value="{{ $option->diagnosis_id }}" wire:model="mainDiagnosisId" class="size-4 accent-charcoal">
                    {{ $option->subjectName() }} · {{ $option->product_name }}
                </label>
            @endforeach
            <x-form.error name="mainDiagnosisId" id="mainDiagnosisId" />
        </fieldset>
    @endif

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
        <x-recorder :questions="$questions" />
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
