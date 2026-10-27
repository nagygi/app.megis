{{-- V6·c · Beszélgetés (10). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($extracting || $dictations !== []) wire:poll.2s="pollDictations" @endif>
    <x-form.error name="answers" id="answers" />

    <ol class="flex flex-col gap-4">
        @foreach (\App\Models\ProcessConversation::QUESTIONS as $question)
            @php($field = 'answers.'.$question)
            <x-ui.card as="li" wire:key="question-{{ $question }}">
                <x-form.long-text
                    :name="$field"
                    :id="'answer-'.$question"
                    wire:model.live.blur="answers.{{ $question }}"
                    :label="__('processes.conversation.answer', ['number' => $question])"
                    rows="3"
                    :max="3000"
                    :pending="isset($dictations[$field])"
                    :tools="$editable"
                    :disabled="! $editable"
                >
                    <x-slot:caption class="flex gap-3 font-semibold">
                        <span class="grid size-7 shrink-0 place-items-center rounded-pill bg-coral text-xs font-extrabold text-black" aria-hidden="true">{{ $question }}</span>
                        {{ __('processes.conversation.questions.'.$question) }}
                    </x-slot:caption>
                </x-form.long-text>
            </x-ui.card>
        @endforeach
    </ol>

    <x-ui.card as="section" tone="cream" bordered class="flex flex-col gap-3" aria-labelledby="going-on" aria-live="polite">
        <h3 id="going-on" class="font-semibold">
            {{ __('processes.list.going_on') }}
            <span class="font-normal text-grey-700">· {{ __('processes.conversation.count', ['processes' => $processCount, 'unclear' => $unclearCount]) }}</span>
        </h3>
        @if ($extracting)
            <p class="text-sm text-grey-700" role="status">{{ __('processes.conversation.extracting') }}</p>
        @endif
        <ul class="flex flex-wrap gap-2">
            @foreach ($chips as $chip)
                <li wire:key="chip-{{ $chip['question'] }}-{{ $chip['index'] }}" @class([
                    'inline-flex min-h-8 items-center gap-1 rounded-pill py-0.5 pl-3 text-sm',
                    'pr-3' => ! $editable,
                    'pr-0.5' => $editable,
                    'bg-charcoal font-semibold text-cream' => ! $chip['unclear'],
                    'border-2 border-dashed border-charcoal' => $chip['unclear'],
                ])>
                    <span>
                        {{ $chip['name'] }}@if ($chip['unclear'])?@endif
                        @if ($chip['rhythm_label'] || $chip['headcount'])
                            <span class="font-normal">· {{ collect([$chip['rhythm_label'], $chip['headcount'] ? __('processes.headcount', ['count' => $chip['headcount']]) : null])->filter()->implode(', ') }}</span>
                        @endif
                    </span>
                    @if ($editable)
                        {{-- 32px: inside a chip; the chip's text is the bigger target around it. --}}
                        <button
                            type="button"
                            wire:click="removeChip({{ $chip['question'] }}, {{ $chip['index'] }})"
                            @class(['press grid size-8 place-items-center rounded-pill', 'hover:bg-grey-700' => ! $chip['unclear'], 'hover:bg-yellow-pale' => $chip['unclear']])
                            aria-label="{{ __('processes.conversation.remove_chip', ['name' => $chip['name']]) }}"
                            title="{{ __('processes.conversation.remove_chip', ['name' => $chip['name']]) }}"
                        >
                            <x-megis.icon name="close" />
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    @if ($editable)
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button size="lg" icon-right="arrow-right" data-focus-errors wire:click="finish" wire:target="finish">{{ __('processes.conversation.finish') }}</x-ui.button>
            <x-ui.button tone="ghost" :href="route('client.diagnosis.process-calendar', [$organization, $diagnosis])">{{ __('processes.conversation.calendar_instead') }}</x-ui.button>
            <x-diagnosis.saved />
        </div>
    @endif
</div>
