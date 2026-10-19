{{-- V4 · A márkád alapja (08 · 8.2). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <x-ui.flash />

    <div class="flex flex-col gap-1">
        <p class="text-xl font-semibold">{{ __('diagnosis.brand.question') }}</p>
        <p class="text-grey-700" aria-live="polite">{{ __('diagnosis.brand.count', ['done' => $doneCount]) }}</p>
    </div>

    @if (! $canEdit)
        <p class="rounded-card bg-grey-100 p-4 text-sm">
            {{ $givenBy ? __('diagnosis.brand.given_by', ['name' => $givenBy]) : __('diagnosis.organization_values.readonly') }}
        </p>
    @endif

    <ul class="flex flex-col gap-3">
        {{-- Értékek (gyökér): the values of V3, listed --}}
        <li class="flex flex-col gap-2 rounded-card bg-white p-5">
            <div class="flex items-center gap-3">
                <x-megis.symbol :name="\App\Enums\TreeElement::Root->symbol()" class="size-8 text-charcoal" />
                <h2 class="font-semibold">{{ \App\Enums\TreeElement::Root->label() }}</h2>
            </div>
            <p class="text-sm text-grey-600">{{ __('diagnosis.brand.questions.root') }}</p>
            @if ($names->isNotEmpty())
                <p>{{ $names->implode(' · ') }}</p>
            @else
                <p class="text-grey-600 italic">{{ __('diagnosis.brand.root_empty') }}</p>
            @endif
            @if ($names->count() >= \App\Models\OrganizationValueSet::MIN_VALUES)
                <p class="inline-flex items-center gap-1 text-sm font-semibold text-green"><x-megis.icon name="check" class="size-4" /> {{ __('diagnosis.brand.done') }}</p>
            @endif
        </li>

        @foreach (\App\Models\OrganizationValueSet::BRAND_FIELDS as $field)
            @php
                $element = $this->elementOf($field);
                $isDone = filled($this->{$field});
                $isEditing = $canEdit && (! $isDone || $editing === $field);
            @endphp
            <li wire:key="brand-{{ $field }}" class="flex flex-col gap-2 rounded-card bg-white p-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <x-megis.symbol :name="$element->symbol()" class="size-8 text-charcoal" />
                        <h2 class="font-semibold">{{ $element->label() }}</h2>
                    </div>
                    @if ($canEdit && $isDone && ! $isEditing)
                        <button type="button" wire:click="edit('{{ $field }}')" class="text-sm font-semibold underline">{{ __('diagnosis.edit') }}</button>
                    @endif
                </div>
                <p class="text-sm text-grey-600" id="brand-{{ $field }}-question">{{ __('diagnosis.brand.questions.'.$field) }}</p>

                @if ($isEditing)
                    <label for="brand-{{ $field }}" class="sr-only">{{ $element->label() }}</label>
                    <textarea
                        id="brand-{{ $field }}"
                        wire:model.live.blur="{{ $field }}"
                        rows="3"
                        maxlength="500"
                        aria-describedby="brand-{{ $field }}-question"
                        placeholder="{{ __('diagnosis.brand.placeholders.'.$field) }}"
                        class="w-full rounded-xl border border-grey-350 bg-white px-3.5 py-2.5 text-base placeholder:text-grey-600 placeholder:italic focus:border-charcoal"
                    ></textarea>
                    <x-form.error :name="$field" :id="'brand-'.$field" />
                    <x-diagnosis.dictate :field="$field" :label="$element->label()" :pending="isset($dictations[$field])" />
                @elseif ($isDone)
                    <p>{{ $this->{$field} }}</p>
                @else
                    <p class="text-grey-600 italic">{{ __('diagnosis.brand.placeholders.'.$field) }}</p>
                @endif

                @if ($isDone)
                    <p class="inline-flex items-center gap-1 text-sm font-semibold text-green"><x-megis.icon name="check" class="size-4" /> {{ __('diagnosis.brand.done') }}</p>
                @endif
            </li>
        @endforeach
    </ul>

    <div class="flex flex-wrap items-center gap-4">
        <x-ui.button tone="coral" size="lg" wire:click="next" :disabled="$canEdit && $doneCount < 4">{{ __('diagnosis.next') }}</x-ui.button>
        <x-diagnosis.saved />
    </div>
</div>
