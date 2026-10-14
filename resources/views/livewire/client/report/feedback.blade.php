{{-- R2·c · Magatokra ismertek? (28 · 28.1). --}}
<section class="flex flex-col gap-3 rounded-card bg-white p-5" aria-labelledby="feedback-{{ $chapter }}" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <h3 id="feedback-{{ $chapter }}" class="font-semibold">{{ __('report.feedback.question') }}</h3>

    @if ($latest)
        <p class="text-sm text-grey-700" role="status">
            {{ $latest->answer === 'yes'
                ? __('report.feedback.sent_yes', ['date' => $latest->created_at->format('m.d.')])
                : __('report.feedback.sent', ['date' => $latest->created_at->format('m.d.'), 'answer' => __('report.feedback.answers.'.$latest->answer)]) }}
        </p>
    @endif

    @if ($open)
        <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('report.feedback.question') }}">
            @foreach (\App\Models\ReportFeedback::ANSWERS as $option)
                <button
                    type="button"
                    wire:click="choose('{{ $option }}')"
                    @class([
                        'rounded-pill border-[1.5px] px-4 py-1.5 text-sm font-semibold',
                        'border-charcoal bg-charcoal text-cream' => $answer === $option,
                        'border-charcoal text-charcoal hover:bg-charcoal/5' => $answer !== $option,
                    ])
                    aria-pressed="{{ $answer === $option ? 'true' : 'false' }}"
                >{{ __('report.feedback.answers.'.$option) }}</button>
            @endforeach
        </div>

        @if (in_array($answer, ['partial', 'no'], true))
            <form wire:submit="send" class="flex flex-col gap-2">
                <x-form.textarea name="text" wire:model="text" :label="__('report.feedback.what')" rows="3" required />
                <x-diagnosis.dictate field="text" :label="__('report.feedback.what')" :pending="isset($dictations['text'])" />
                <div>
                    <x-ui.button type="submit" size="sm">{{ __('report.feedback.send') }}</x-ui.button>
                </div>
            </form>
        @endif
    @endif
</section>
