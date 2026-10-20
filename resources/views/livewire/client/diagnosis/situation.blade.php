{{-- "Egy helyzet" (12 · 12.3): spoken or typed. --}}
<div class="flex max-w-xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h2 class="text-xl font-semibold">{{ __('processes.occasions.situation_prompt') }}</h2>
        <p class="text-grey-700">{{ __('processes.occasions.situation_hint') }}</p>
    </div>

    <x-ui.segmented :label="__('processes.occasions.situation')">
        @foreach (['record' => __('mirror.mode_record'), 'text' => __('mirror.mode_text')] as $key => $label)
            <x-ui.segment :selected="$mode === $key" wire:click="setMode('{{ $key }}')">{{ $label }}</x-ui.segment>
        @endforeach
    </x-ui.segmented>

    @if ($mode === 'record')
        <x-recorder :questions="$questions" />
    @else
        <form wire:submit="submitText" class="flex flex-col gap-4">
            <x-form.textarea name="text" wire:model="text" rows="8" :label="__('processes.occasions.situation_text')" maxlength="{{ config('megis.materials.text_max_characters') }}" required />
            <div>
                <x-ui.button type="submit" size="lg" icon-right="arrow-right" data-focus-errors>{{ __('diagnosis.next') }}</x-ui.button>
            </div>
        </form>
    @endif
</div>
