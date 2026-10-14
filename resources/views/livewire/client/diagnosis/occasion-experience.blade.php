{{-- V11·c · A megélésed (12 · 12.4). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <div class="flex flex-col gap-1">
        <p class="text-xl font-semibold">{{ __('processes.occasions.experience_question') }}</p>
        <p class="text-grey-700" aria-live="polite">{{ __('diagnosis.experience.count', ['answered' => $answeredCount, 'total' => $total]) }}</p>
    </div>

    <x-form.error name="answers" id="answers" />

    <div class="flex flex-col gap-1.5">
        <x-form.textarea name="note" wire:model.blur="note" :label="__('processes.occasions.note')" rows="2" :disabled="! $editable" />
        @if ($editable)
            <x-diagnosis.dictate field="note" :label="__('processes.occasions.note')" :pending="isset($dictations['note'])" />
        @endif
    </div>

    @foreach ([['title' => __('diagnosis.experience.values'), 'rows' => $valueRows], ['title' => __('diagnosis.experience.brand'), 'rows' => $treeRows]] as $block)
        <section class="flex flex-col gap-3" aria-label="{{ $block['title'] }}">
            <h2 class="text-lg font-semibold">{{ $block['title'] }}</h2>
            @foreach ($block['rows'] as $row)
                <div wire:key="row-{{ $row['key'] }}" class="flex flex-col gap-3 rounded-card bg-white p-5">
                    <p class="font-semibold">{{ $row['label'] }}</p>
                    <x-diagnosis.scale :name="'answers.'.$row['key']" :label="$row['label']" :disabled="! $editable" />
                </div>
            @endforeach
        </section>
    @endforeach

    <fieldset class="flex flex-col gap-2 rounded-card bg-white p-5" @disabled(! $editable)>
        <legend class="mb-1 font-semibold">{{ __('processes.occasions.effective') }}</legend>
        <div class="flex flex-wrap gap-2">
            @foreach (\App\Models\OccasionExperience::EFFECTIVENESS as $option)
                <label class="relative">
                    <input type="radio" name="effectiveness" value="{{ $option }}" wire:model.live="effectiveness" class="peer sr-only">
                    <span class="inline-flex h-10 cursor-pointer items-center rounded-pill border border-grey-350 bg-white px-4 text-sm font-semibold peer-checked:border-charcoal peer-checked:bg-charcoal peer-checked:text-cream peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">{{ __('processes.occasions.effectiveness.'.$option) }}</span>
                </label>
            @endforeach
        </div>
        <x-form.error name="effectiveness" id="effectiveness" />
    </fieldset>

    @if ($editable)
        <div class="flex flex-wrap items-center gap-4">
            <x-ui.button tone="coral" size="lg" wire:click="submit">{{ __('processes.occasions.send') }}</x-ui.button>
            <x-ui.button tone="outline" wire:click="skip">{{ __('processes.occasions.skip') }}</x-ui.button>
        </div>
    @endif
</div>
