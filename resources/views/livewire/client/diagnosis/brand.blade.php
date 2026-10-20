{{-- V4 · A márkád alapja (08 · 8.2). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <x-ui.flash />

    <div class="flex flex-col gap-1">
        <h2 class="text-xl font-semibold">{{ __($texts.'.question') }}</h2>
        <p class="text-grey-700" aria-live="polite">{{ __('diagnosis.brand.count', ['done' => $doneCount]) }}</p>
    </div>

    @if (! $canEdit)
        <x-ui.notice>{{ $givenBy ? __('diagnosis.brand.given_by', ['name' => $givenBy]) : __('diagnosis.organization_values.readonly') }}</x-ui.notice>
    @endif

    <ul class="flex flex-col gap-3">
        {{-- Értékek (gyökér): the values of V3, listed --}}
        <x-ui.card as="li" class="flex flex-col gap-2">
            <div class="flex items-center gap-3">
                <x-megis.symbol :name="\App\Enums\TreeElement::Root->symbol()" class="size-8 text-charcoal" />
                <h3 class="text-lg font-semibold">{{ \App\Enums\TreeElement::Root->label() }}</h3>
            </div>
            <p class="text-sm text-grey-700">{{ __($texts.'.questions.root') }}</p>
            @if ($names->isNotEmpty())
                <p>{{ $names->implode(' · ') }}</p>
            @else
                <p class="text-grey-600 italic">{{ __($texts.'.root_empty') }}</p>
            @endif
            @if ($names->count() >= \App\Models\OrganizationValueSet::MIN_VALUES)
                <p class="inline-flex items-center gap-1 text-sm font-semibold text-green"><x-megis.icon name="check" /> {{ __('diagnosis.brand.done') }}</p>
            @endif
        </x-ui.card>

        @foreach (\App\Models\OrganizationValueSet::BRAND_FIELDS as $field)
            @php
                $element = $this->elementOf($field);
                $isDone = filled($this->{$field});
                $isEditing = $canEdit && (! $isDone || $editing === $field);
            @endphp
            <x-ui.card as="li" wire:key="brand-{{ $field }}" class="flex flex-col gap-2">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <x-megis.symbol :name="$element->symbol()" class="size-8 text-charcoal" />
                        <h3 class="text-lg font-semibold">{{ $element->label() }}</h3>
                    </div>
                    @if ($canEdit && $isDone && ! $isEditing)
                        <x-ui.button tone="ghost" size="sm" wire:click="edit('{{ $field }}')">{{ __('diagnosis.edit') }}</x-ui.button>
                    @endif
                </div>

                @if ($isEditing)
                    {{-- The question is the field's label; the example sits in it until the answer comes. --}}
                    <x-form.textarea
                        :name="$field"
                        :id="'brand-'.$field"
                        wire:model.live.blur="{{ $field }}"
                        :label="__($texts.'.questions.'.$field)"
                        required
                        rows="3"
                        maxlength="500"
                        placeholder="{{ __($texts.'.placeholders.'.$field) }}"
                        class="placeholder:italic"
                    />
                    <x-diagnosis.dictate :field="$field" :label="$element->label()" :pending="isset($dictations[$field])" />
                @else
                    <p class="text-sm text-grey-700">{{ __($texts.'.questions.'.$field) }}</p>
                    @if ($isDone)
                        <p>{{ $this->{$field} }}</p>
                    @else
                        <p class="text-grey-600 italic">{{ __($texts.'.placeholders.'.$field) }}</p>
                    @endif
                @endif

                @if ($isDone)
                    <p class="inline-flex items-center gap-1 text-sm font-semibold text-green"><x-megis.icon name="check" /> {{ __('diagnosis.brand.done') }}</p>
                @endif
            </x-ui.card>
        @endforeach
    </ul>

    <x-diagnosis.next wire:click="next" :disabled-reason="$canEdit && $doneCount < 4 ? __('diagnosis.brand.next_hint') : null" />
</div>
