{{-- "Egy helyzet" (12 · 12.3): spoken or typed. --}}
<div class="flex max-w-xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <p class="text-xl font-semibold">{{ __('processes.occasions.situation_prompt') }}</p>
        <p class="text-grey-700">{{ __('processes.occasions.situation_hint') }}</p>
    </div>

    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('processes.occasions.situation') }}">
        @foreach (['record' => __('mirror.mode_record'), 'text' => __('mirror.mode_text')] as $key => $label)
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
    @else
        <form wire:submit="submitText" class="flex flex-col gap-4">
            <x-form.textarea name="text" wire:model="text" rows="8" :label="__('processes.occasions.situation_text')" maxlength="{{ config('megis.materials.text_max_characters') }}" required />
            <div>
                <x-ui.button type="submit" size="lg">{{ __('diagnosis.next') }}</x-ui.button>
            </div>
        </form>
    @endif
</div>
