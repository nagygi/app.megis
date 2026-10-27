{{-- Képességek (NM-12): situations told to the values, typed or spoken; the step after the Megélés. --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <p class="text-grey-700">{{ trans_choice('capabilities.step.count', $suggested, ['count' => $suggested]) }}</p>

    <p>{{ __($isPeer ? 'capabilities.step.lead_peer' : 'capabilities.step.lead') }}</p>

    @unless ($editable)
        <x-ui.notice>{{ __('diagnosis.locked') }}</x-ui.notice>
    @endunless

    <x-form.error name="answers" id="answers" />

    <ol class="flex flex-col gap-3">
        @foreach ($stories as $story)
            @php($field = 'answers.'.$story->id)
            @php($value = collect($values)->firstWhere('key', $story->value_key))
            <x-ui.card as="li" wire:key="story-{{ $story->id }}" class="flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-1 pt-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold">{{ $story->value_name }}</h3>
                            @if ($story->value_source === \App\Models\CapabilityStory::SOURCE_OWN)
                                <x-ui.badge tone="coral">{{ __('capabilities.step.source.own') }}</x-ui.badge>
                            @elseif ($value['yours_too'] ?? false)
                                <x-ui.badge tone="coral">{{ __('capabilities.step.source.yours_too') }}</x-ui.badge>
                            @else
                                <x-ui.badge tone="muted">{{ __('capabilities.step.source.organization') }}</x-ui.badge>
                            @endif
                        </div>
                        @if (filled($story->value_meaning))
                            <p class="text-sm text-grey-700">{{ $story->value_meaning }}</p>
                        @endif
                    </div>
                    @if ($editable && $stories->count() > 1)
                        <x-ui.icon-button
                            icon="close"
                            class="-mt-1 -mr-2"
                            :label="__('capabilities.step.remove', ['name' => $story->value_name])"
                            wire:click="removeStory({{ $story->id }})"
                            wire:confirm="{{ __('capabilities.step.remove_confirm') }}"
                            data-confirm-tone="danger"
                            :data-confirm-label="__('capabilities.step.remove_yes')"
                        />
                    @endif
                </div>

                <x-form.long-text
                    :name="$field"
                    :id="'story-'.$story->id"
                    wire:model.live.blur="answers.{{ $story->id }}"
                    :label="$story->value_name"
                    rows="4"
                    :max="3000"
                    placeholder="{{ __('capabilities.step.placeholder') }}"
                    :pending="isset($dictations[$field])"
                    :tools="$editable"
                    :disabled="! $editable"
                >
                    <x-slot:caption class="font-semibold">{{ $story->question }}</x-slot:caption>
                </x-form.long-text>

                @if ($editable && count($values) > 1)
                    <x-ui.disclosure :summary="__('capabilities.step.other_value')">
                        <x-form.select
                            :name="'value-'.$story->id"
                            :label="__('capabilities.step.value_label')"
                            x-on:change="$wire.changeValue({{ $story->id }}, $event.target.value)"
                        >
                            @foreach ($values as $option)
                                <option value="{{ $option['key'] }}" @selected($option['key'] === $story->value_key)>{{ $option['name'] }}</option>
                            @endforeach
                        </x-form.select>
                    </x-ui.disclosure>
                @endif
            </x-ui.card>
        @endforeach
    </ol>

    @if ($editable && $stories->count() < $max)
        <div>
            <x-ui.button tone="ghost" icon="plus" wire:click="addStory">{{ __('capabilities.step.add') }}</x-ui.button>
        </div>
    @endif

    <p class="text-sm text-grey-700">{{ __('capabilities.step.privacy', ['count' => $minCharacters]) }}</p>

    @if ($editable)
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button size="lg" icon-right="arrow-right" data-focus-errors wire:click="submit" wire:target="submit">{{ __('capabilities.step.submit') }}</x-ui.button>
            <x-ui.button tone="outline" wire:click="skip">{{ __('capabilities.step.skip') }}</x-ui.button>
            <x-diagnosis.saved />
        </div>
    @endif
</div>
