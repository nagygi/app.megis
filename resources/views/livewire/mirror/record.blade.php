{{-- ET2 · the recording (06 · 6.1). One question, three ways to answer; public, the e-mail part comes last, right above the button. --}}
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
        {{-- The order and the words of every longer text (x-form.long-text); telling stays the default here. --}}
        @foreach (['text' => __('ui.long_text.write'), 'record' => __('ui.long_text.speak'), 'file' => __('ui.long_text.upload')] as $key => $label)
            <x-ui.segment :selected="$mode === $key" wire:click="setMode('{{ $key }}')">{{ $label }}</x-ui.segment>
        @endforeach
    </x-ui.segmented>

    @if ($mode === 'record')
        <x-recorder :questions="$questions" check="checkBeforeUpload" :inline-submit="false" />
    @elseif ($mode === 'text')
        <x-form.textarea name="text" wire:model="text" rows="10" :label="__('mirror.text_label')" :help="__('mirror.text_help')" maxlength="{{ config('megis.materials.text_max_characters') }}" required />
    @else
        {{-- Framed like the recorder, so the three read as tabs of one panel. --}}
        <x-ui.card padding="lg">
            <x-form.file
                name="file"
                id="mirror-file"
                wire:model="file"
                accept=".{{ implode(',.', config('megis.materials.text_extensions')) }}"
                :label="__('mirror.file_label')"
                :help="__('mirror.file_help', $textRules)"
                required
            />
        </x-ui.card>
    @endif

    @if ($isPublic)
        {{-- The first part of the registration: told freely first, filled in right before sending (06 · 6.1) --}}
        <x-ui.card as="section" aria-labelledby="mirror-contact-title" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 id="mirror-contact-title" class="text-lg font-semibold">{{ __('mirror.contact_title') }}</h2>
                <p class="text-sm text-grey-700">{{ __('mirror.contact_lead') }}</p>
            </div>

            <x-form.input name="email" type="email" wire:model="email" :label="__('mirror.email')" autocomplete="email" required />

            {{-- Its label carries the two links, so it is built here; it looks like x-form.checkbox. --}}
            <div class="flex flex-col gap-1.5">
                <div class="flex items-start gap-3">
                    <input id="mirror-consent" type="checkbox" wire:model="consent" class="mt-0.5 size-5 shrink-0 accent-charcoal" @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror>
                    <label for="mirror-consent">
                        {{ __('mirror.consent_before') }}
                        @if ($termsUrl)<a href="{{ $termsUrl }}" target="_blank" rel="noopener" class="font-semibold">{{ __('mirror.consent_terms') }}<span class="sr-only"> ({{ __('ui.new_tab') }})</span></a>@else{{ __('mirror.consent_terms') }}@endif{{ __('mirror.consent_middle') }}
                        @if ($privacyUrl)<a href="{{ $privacyUrl }}" target="_blank" rel="noopener" class="font-semibold">{{ __('mirror.consent_privacy') }}<span class="sr-only"> ({{ __('ui.new_tab') }})</span></a>@else{{ __('mirror.consent_privacy') }}@endif
                    </label>
                </div>
                <x-form.error name="consent" id="consent" class="pl-8" />
            </div>
        </x-ui.card>
    @endif

    {{-- One button for the three ways; Turnstile stays in place while the way changes --}}
    <div class="flex flex-col gap-4">
        @if ($isPublic)
            <x-turnstile model="turnstileToken" />
        @endif

        @if ($mode === 'record')
            <x-recorder-submit wire:key="submit-record">{{ __('mirror.submit') }}</x-recorder-submit>
        @elseif ($mode === 'text')
            <div wire:key="submit-text">
                <x-ui.button size="lg" icon-right="arrow-right" wire:click="submitText" data-focus-errors>{{ __('mirror.submit') }}</x-ui.button>
            </div>
        @else
            <div wire:key="submit-file">
                <x-ui.button size="lg" icon-right="arrow-right" wire:click="submitFile" wire:loading.attr="disabled" wire:target="file" data-focus-errors>{{ __('mirror.submit') }}</x-ui.button>
            </div>
        @endif
    </div>

    @if ($previousMirrors->isNotEmpty())
        {{-- The earlier mirrors, their values expanded and the full picture (06 · 6.2) --}}
        <section aria-labelledby="previous-mirrors-title" class="flex flex-col gap-3">
            <x-ui.section-header :title="__('mirror.previous_mirrors')" id="previous-mirrors-title" />
            <ul class="flex flex-col gap-2">
                @foreach ($previousMirrors as $previous)
                    <li wire:key="previous-{{ $previous['session']->id }}">
                        <x-ui.link-row
                            :href="route('client.mirror.result', [$organization, $previous['session']])"
                            :title="__('mirror.diagnosis_name', ['date' => $previous['session']->created_at->format('Y.m.d.')])"
                            :meta="$previous['values']"
                            symbol="growth-rings"
                        />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
