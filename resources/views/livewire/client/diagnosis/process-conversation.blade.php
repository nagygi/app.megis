{{-- V6·c · Beszélgetés (10). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($extracting || $dictations !== []) wire:poll.2s="pollDictations" @endif>
    <div class="flex flex-col gap-1">
        <p class="text-xl font-semibold">{{ __('processes.conversation.lead') }}</p>
        <p class="text-grey-700">{{ __('processes.conversation.duration') }}</p>
    </div>

    <x-form.error name="answers" id="answers" />

    <ol class="flex flex-col gap-4">
        @foreach (\App\Models\ProcessConversation::QUESTIONS as $question)
            <li wire:key="question-{{ $question }}" class="flex flex-col gap-2 rounded-card bg-white p-5">
                <label for="answer-{{ $question }}" class="flex gap-3 font-semibold">
                    <span class="grid size-7 shrink-0 place-items-center rounded-pill bg-coral text-xs font-extrabold text-black" aria-hidden="true">{{ $question }}</span>
                    {{ __('processes.conversation.questions.'.$question) }}
                </label>
                <textarea
                    id="answer-{{ $question }}"
                    wire:model.live.blur="answers.{{ $question }}"
                    rows="3"
                    maxlength="3000"
                    @disabled(! $editable)
                    class="w-full rounded-field border border-grey-350 bg-white px-3.5 py-2.5 text-base focus:border-charcoal"
                ></textarea>
                <x-form.error :name="'answers.'.$question" :id="'answer-'.$question" />
                @if ($editable)
                    <x-diagnosis.dictate :field="'answers.'.$question" :label="__('processes.conversation.answer', ['number' => $question])" :pending="isset($dictations['answers.'.$question])" />
                @endif
            </li>
        @endforeach
    </ol>

    <section class="flex flex-col gap-3 rounded-card border border-grey-250 p-5" aria-labelledby="going-on" aria-live="polite">
        <h2 id="going-on" class="font-semibold">
            {{ __('processes.list.going_on') }}
            <span class="font-normal text-grey-700">· {{ __('processes.conversation.count', ['processes' => $processCount, 'unclear' => $unclearCount]) }}</span>
        </h2>
        @if ($extracting)
            <p class="text-sm text-grey-700" role="status">{{ __('processes.conversation.extracting') }}</p>
        @endif
        <ul class="flex flex-wrap gap-2">
            @foreach ($chips as $chip)
                <li wire:key="chip-{{ $chip['question'] }}-{{ $chip['index'] }}" @class([
                    'inline-flex items-center gap-2 rounded-pill px-3 py-1 text-sm',
                    'bg-charcoal font-semibold text-cream' => ! $chip['unclear'],
                    'border-2 border-dashed border-charcoal' => $chip['unclear'],
                ])>
                    <span>
                        {{ $chip['name'] }}@if ($chip['unclear'])?@endif
                        @if ($chip['rhythm_label'] || $chip['headcount'])
                            <span class="font-normal opacity-80">· {{ collect([$chip['rhythm_label'], $chip['headcount'] ? __('processes.headcount', ['count' => $chip['headcount']]) : null])->filter()->implode(', ') }}</span>
                        @endif
                    </span>
                    @if ($editable)
                        <button type="button" wire:click="removeChip({{ $chip['question'] }}, {{ $chip['index'] }})" class="rounded-pill">
                            <x-megis.icon name="close" class="size-3" :label="__('processes.conversation.remove_chip', ['name' => $chip['name']])" />
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    @if ($editable)
        <div class="flex flex-wrap items-center gap-4">
            <x-ui.button tone="coral" size="lg" wire:click="finish" wire:loading.attr="disabled" wire:target="finish">{{ __('processes.conversation.finish') }}</x-ui.button>
            <a href="{{ route('client.diagnosis.process-calendar', [$organization, $diagnosis]) }}" class="font-semibold">{{ __('processes.conversation.calendar_instead') }}</a>
            <x-diagnosis.saved />
        </div>
    @endif
</div>
