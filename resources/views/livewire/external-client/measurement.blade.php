{{-- Ügyfélmérés (35 · 35.6): consent, one question per screen, overview, sent. --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    @if (! $open)
        <x-ui.card class="flex flex-col gap-3">
            <h2 class="text-xl font-semibold">{{ __('external_clients.measurement.closed_title') }}</h2>
            <p>{{ __('external_clients.measurement.closed') }}</p>
        </x-ui.card>
    @elseif ($items->isEmpty())
        <x-ui.notice>{{ __('external_clients.measurement.no_items') }}</x-ui.notice>
    @elseif ($screen === 'intro')
        <p>{{ __('external_clients.measurement.intro', ['organization' => $organization->name, 'count' => $items->count()]) }}</p>
        <p class="text-grey-700">{{ __('external_clients.measurement.intro_deadline', ['date' => $deadline]) }}</p>

        <x-ui.card class="flex flex-col gap-4">
            <x-form.checkbox name="consent" wire:model="consent" :label="__('external_clients.measurement.consent', ['organization' => $organization->name])" />
        </x-ui.card>

        <div>
            <x-ui.button size="lg" icon-right="arrow-right" data-focus-errors wire:click="start">{{ __('external_clients.measurement.start') }}</x-ui.button>
        </div>
    @elseif ($screen === 'question' && $current !== null)
        @php
            $isValue = $current->kind === \App\Enums\ExternalClientItemKind::Value;
            $subject = $isValue ? ($valueNames[$current->value_key] ?? '') : $current->process?->name;
        @endphp
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-grey-700">
                {{ $isValue ? __('external_clients.measurement.value_label') : __('external_clients.measurement.process_label') }} · <span class="font-semibold text-charcoal">{{ $subject }}</span>
            </p>
            <p class="text-sm text-grey-700 tabular-nums">{{ __('external_clients.measurement.progress', ['current' => $position + 1, 'total' => $items->count()]) }}</p>
        </div>

        <x-ui.card class="flex flex-col gap-3" wire:key="external-question-{{ $current->id }}">
            <x-form.long-text
                :name="'answers.'.$current->id"
                :id="'external-answer-'.$current->id"
                wire:model.live.blur="answers.{{ $current->id }}"
                :label="__('external_clients.measurement.answer')"
                rows="8"
                :max="$maxCharacters"
                :pending="isset($dictations['answers.'.$current->id])"
            >
                <x-slot:caption class="font-semibold">{{ $current->question }}</x-slot:caption>
            </x-form.long-text>
        </x-ui.card>

        <div class="flex flex-wrap items-center gap-3">
            @if ($position > 0)
                <x-ui.button tone="outline" icon="chevron-left" wire:click="previous">{{ __('external_clients.measurement.previous') }}</x-ui.button>
            @endif
            @if ($position < $items->count() - 1)
                <x-ui.button size="lg" icon-right="arrow-right" data-focus-errors wire:click="next">{{ __('external_clients.measurement.next') }}</x-ui.button>
                <x-ui.button tone="ghost" wire:click="summary">{{ __('external_clients.measurement.to_summary') }}</x-ui.button>
            @else
                <x-ui.button size="lg" icon-right="arrow-right" data-focus-errors wire:click="next">{{ __('external_clients.measurement.to_summary') }}</x-ui.button>
            @endif
        </div>
    @elseif ($screen === 'summary')
        <h2 class="text-xl font-semibold">{{ __('external_clients.measurement.summary_title') }}</h2>
        <p class="text-grey-700">{{ __('external_clients.measurement.summary_lead') }}</p>

        <ol class="flex flex-col gap-4">
            @foreach ($items as $index => $item)
                @php
                    $isValue = $item->kind === \App\Enums\ExternalClientItemKind::Value;
                    $subject = $isValue ? ($valueNames[$item->value_key] ?? '') : $item->process?->name;
                    $text = trim($answers[$item->id] ?? '');
                @endphp
                <li wire:key="external-summary-{{ $item->id }}">
                    <x-ui.card class="flex flex-col gap-2">
                        <p class="text-sm text-grey-700">{{ $isValue ? __('external_clients.measurement.value_label') : __('external_clients.measurement.process_label') }} · <span class="font-semibold text-charcoal">{{ $subject }}</span></p>
                        <p class="font-semibold">{{ $item->question }}</p>
                        <p class="whitespace-pre-line {{ $text === '' ? 'text-grey-600' : '' }}">{{ $text !== '' ? $text : __('external_clients.measurement.empty_answer') }}</p>
                        <div>
                            <x-ui.button tone="outline" size="sm" wire:click="edit({{ $index }})">{{ __('external_clients.measurement.edit') }}</x-ui.button>
                        </div>
                    </x-ui.card>
                </li>
            @endforeach
        </ol>

        <x-form.error name="answers" id="external-answers" />

        <div>
            <x-ui.button size="lg" icon-right="arrow-right" data-focus-errors wire:click="submit" wire:target="submit">{{ __('external_clients.measurement.submit') }}</x-ui.button>
        </div>
    @elseif ($screen === 'sent')
        <x-ui.card class="flex flex-col gap-3">
            <h2 class="text-xl font-semibold">{{ __('external_clients.measurement.sent_title') }}</h2>
            <p>{{ __('external_clients.measurement.sent', ['date' => $deadline]) }}</p>
            <div>
                <x-ui.button tone="outline" wire:click="summary">{{ __('external_clients.measurement.review') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif
</div>
