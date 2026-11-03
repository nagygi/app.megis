{{-- Képességek (NM-12): recent situations in one text, typed, spoken or uploaded; the step after the Megélés. --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <p class="text-grey-700">{{ __('capabilities.step.format') }}</p>

    <p>{{ __($isPeer ? 'capabilities.step.lead_peer' : 'capabilities.step.lead') }}</p>

    @unless ($editable)
        <x-ui.notice>{{ __('diagnosis.locked') }}</x-ui.notice>
    @endunless

    {{-- No capability is named here: the text is read against the Képességtár afterwards, unled. --}}
    <x-ui.card class="flex flex-col gap-3">
        <x-form.long-text
            name="answer"
            id="capability-story"
            wire:model.live.blur="answer"
            :label="__('capabilities.step.label')"
            rows="8"
            :max="$maxCharacters"
            placeholder="{{ __('capabilities.step.placeholder') }}"
            :pending="isset($dictations['answer'])"
            :tools="$editable"
            :disabled="! $editable"
        >
            <x-slot:caption class="flex flex-col gap-1">
                <span class="font-semibold">{{ __('capabilities.step.prompt') }}</span>
                <span class="text-sm text-grey-700">{{ __('capabilities.step.hints') }}</span>
            </x-slot:caption>
        </x-form.long-text>
    </x-ui.card>

    <p class="text-sm text-grey-700">{{ __('capabilities.step.privacy', ['count' => $minCharacters]) }}</p>

    @if ($editable)
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button size="lg" icon-right="arrow-right" data-focus-errors wire:click="submit" wire:target="submit">{{ __('capabilities.step.submit') }}</x-ui.button>
            <x-ui.button tone="outline" wire:click="skip">{{ __('capabilities.step.skip') }}</x-ui.button>
            <x-diagnosis.saved />
        </div>
    @endif
</div>
