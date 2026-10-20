{{-- R2·c · Magatokra ismertek? (28 · 28.1). The poll runs only while a dictation is on its way. --}}
<div @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <x-ui.card as="section" class="flex flex-col gap-3" aria-labelledby="feedback-{{ $chapter }}">
        <h3 id="feedback-{{ $chapter }}" class="text-lg font-semibold">{{ __('report.feedback.question') }}</h3>

        @if ($latest)
            <p class="text-sm text-grey-700" role="status">
                {{ $latest->answer === 'yes'
                    ? __('report.feedback.sent_yes', ['date' => $latest->created_at->format('m.d.')])
                    : __('report.feedback.sent', ['date' => $latest->created_at->format('m.d.'), 'answer' => __('report.feedback.answers.'.$latest->answer)]) }}
            </p>
        @endif

        @if ($open)
            <x-ui.segmented :label="__('report.feedback.question')">
                @foreach (\App\Models\ReportFeedback::ANSWERS as $option)
                    <x-ui.segment :selected="$answer === $option" wire:click="choose('{{ $option }}')">{{ __('report.feedback.answers.'.$option) }}</x-ui.segment>
                @endforeach
            </x-ui.segmented>

            @if (in_array($answer, ['partial', 'no'], true))
                <form wire:submit="send" class="flex flex-col gap-2">
                    <x-form.textarea name="text" wire:model="text" :label="__('report.feedback.what')" rows="3" required />
                    <x-diagnosis.dictate field="text" :label="__('report.feedback.what')" :pending="isset($dictations['text'])" />
                    <div>
                        <x-ui.button type="submit" size="sm" data-focus-errors>{{ __('report.feedback.send') }}</x-ui.button>
                    </div>
                </form>
            @endif
        @endif
    </x-ui.card>
</div>
