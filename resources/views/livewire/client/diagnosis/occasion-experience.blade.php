{{--
    V11·c · A megélésed (12 · 12.4). Nothing is saved until "Beküldöm", so
    leaving with answers asks first (the browser's own question).
--}}
<div
    class="flex max-w-2xl flex-col gap-6"
    @if ($dictations !== []) wire:poll.2s="pollDictations" @endif
    x-data="{ dirty: false }"
    x-on:change="dirty = true"
    x-on:beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = '' }"
>
    <div class="flex flex-col gap-1">
        <h2 class="text-xl font-semibold">{{ __('processes.occasions.experience_question') }}</h2>
        <p class="text-grey-700" aria-live="polite">{{ __('diagnosis.experience.count', ['answered' => $answeredCount, 'total' => $total]) }}</p>
    </div>

    <x-form.error name="answers" id="answers" />

    <x-form.long-text
        name="note"
        wire:model.blur="note"
        :label="__('processes.occasions.note')"
        rows="2"
        :max="3000"
        :pending="isset($dictations['note'])"
        :tools="$editable"
        :disabled="! $editable"
    />

    @foreach ([['title' => __('diagnosis.experience.values'), 'rows' => $valueRows], ['title' => __('diagnosis.experience.brand'), 'rows' => $treeRows]] as $block)
        <section class="flex flex-col gap-3" aria-label="{{ $block['title'] }}">
            <h3 class="text-lg font-semibold">{{ $block['title'] }}</h3>
            @foreach ($block['rows'] as $row)
                <x-ui.card wire:key="row-{{ $row['key'] }}" class="flex flex-col gap-3">
                    <p class="font-semibold">{{ $row['label'] }}</p>
                    <x-diagnosis.scale :name="'answers.'.$row['key']" :label="$row['label']" :disabled="! $editable" />
                </x-ui.card>
            @endforeach
        </section>
    @endforeach

    <x-ui.card as="fieldset" class="flex flex-col gap-2" :disabled="! $editable">
        <legend class="float-left mb-2 font-semibold">{{ __('processes.occasions.effective') }}</legend>
        <div class="clear-left flex flex-wrap gap-2">
            @foreach (\App\Models\OccasionExperience::EFFECTIVENESS as $option)
                <label class="relative">
                    <input type="radio" name="effectiveness" value="{{ $option }}" wire:model.live="effectiveness" class="peer sr-only">
                    <span class="inline-flex min-h-10 cursor-pointer items-center rounded-pill border-[1.5px] border-charcoal bg-transparent px-4 text-sm font-semibold transition-colors duration-200 hover:bg-yellow-pale peer-checked:bg-charcoal peer-checked:text-cream peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">{{ __('processes.occasions.effectiveness.'.$option) }}</span>
                </label>
            @endforeach
        </div>
        <x-form.error name="effectiveness" id="effectiveness" />
    </x-ui.card>

    @if ($editable)
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button size="lg" icon-right="arrow-right" data-focus-errors x-on:click="dirty = false" wire:click="submit">{{ __('processes.occasions.send') }}</x-ui.button>
            <x-ui.button tone="outline" x-on:click="dirty = false" wire:click="skip">{{ __('processes.occasions.skip') }}</x-ui.button>
        </div>
    @endif
</div>
