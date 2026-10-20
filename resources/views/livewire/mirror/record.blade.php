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
        <x-ui.card>
            <x-form.radio-group
                name="mainDiagnosisId"
                :label="__('mirror.which_diagnosis')"
                :options="$diagnosisOptions->mapWithKeys(fn ($option) => [$option->diagnosis_id => $option->subjectName().' · '.$option->product_name])->all()"
                wire:model="mainDiagnosisId"
            />
        </x-ui.card>
    @endif

    <x-ui.segmented :label="__('mirror.title')">
        @foreach (['record' => __('mirror.mode_record'), 'text' => __('mirror.mode_text'), 'file' => __('mirror.mode_file')] as $key => $label)
            <x-ui.segment :selected="$mode === $key" wire:click="setMode('{{ $key }}')">{{ $label }}</x-ui.segment>
        @endforeach
    </x-ui.segmented>

    @if ($mode === 'record')
        <x-recorder :questions="$questions" />
    @elseif ($mode === 'text')
        <form wire:submit="submitText" class="flex flex-col gap-4">
            <x-form.textarea name="text" wire:model="text" rows="10" :label="__('mirror.text_label')" :help="__('mirror.text_help')" maxlength="{{ config('megis.materials.text_max_characters') }}" required />
            <div>
                <x-ui.button type="submit" size="lg" icon-right="arrow-right" data-focus-errors>{{ __('mirror.submit') }}</x-ui.button>
            </div>
        </form>
    @else
        <form wire:submit="submitFile" class="flex flex-col gap-4">
            <x-form.file
                name="file"
                id="mirror-file"
                wire:model="file"
                accept=".{{ implode(',.', config('megis.materials.text_extensions')) }}"
                :label="__('mirror.file_label')"
                :help="__('mirror.file_help', $textRules)"
                required
            />
            <div>
                <x-ui.button type="submit" size="lg" icon-right="arrow-right" wire:loading.attr="disabled" wire:target="file" data-focus-errors>{{ __('mirror.submit') }}</x-ui.button>
            </div>
        </form>
    @endif

    @if ($isPublic)
        <x-turnstile model="turnstileToken" class="items-center" />
    @endif
</div>
