{{--
    T1 · the peer's invitation with the consents (25 · 25.2). Nothing is
    ticked in advance: the consent has to be the peer's own act (GDPR).
--}}
<div class="flex max-w-xl flex-col gap-6">
    <p class="text-lg">{{ __('diagnosis.invitation.lead', ['leader' => $leaderName, 'subject' => $main->subjectName(), 'minutes' => config('diagnosis.peer_minutes')]) }}</p>

    @if (! $main->acceptsInput())
        <p class="rounded-card bg-grey-100 p-4">{{ __('diagnosis.invitation.closed') }}</p>
    @else
        <form wire:submit="start" class="flex flex-col gap-5 rounded-card bg-white p-5">
            <div class="flex flex-col gap-1">
                <x-form.checkbox name="answersConsent" wire:model.live="answersConsent" :label="__('diagnosis.consent.answers', ['leader' => $leaderName])" :help="__('diagnosis.consent.answers_help')" />
            </div>

            @if ($main->show_peer_quotes)
                <x-form.checkbox name="quotesConsent" wire:model="quotesConsent" :label="__('diagnosis.consent.quotes')" :help="__('diagnosis.consent.quotes_help')" />
            @endif

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit" tone="dark" size="lg" :disabled="! $answersConsent">{{ __('diagnosis.invitation.start') }}</x-ui.button>
                <a href="{{ route('client.home', $main->diagnosis->organization) }}" class="font-semibold">{{ __('diagnosis.invitation.later') }}</a>
            </div>
        </form>
    @endif
</div>
