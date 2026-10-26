{{--
    T1 · the peer's invitation with the consents (25 · 25.2). Nothing is
    ticked in advance: the consent has to be the peer's own act (GDPR).
--}}
<div class="flex max-w-xl flex-col gap-6">
    <x-ui.flash />

    <p class="text-lg">{{ __('diagnosis.invitation.lead', ['leader' => $leaderName, 'subject' => $main->subjectName(), 'minutes' => $main->peerMinutes()]) }}</p>

    @if (! $main->acceptsInput())
        <x-ui.notice>{{ __('diagnosis.invitation.closed') }}</x-ui.notice>
    @else
        <x-ui.card as="form" wire:submit="start" class="flex flex-col gap-5">
            <x-form.checkbox name="answersConsent" wire:model.live="answersConsent" :label="__('diagnosis.consent.answers', ['leader' => $leaderName])" :help="__('diagnosis.consent.answers_help')" />

            @if ($main->show_peer_quotes)
                <x-form.checkbox name="quotesConsent" wire:model="quotesConsent" :label="__('diagnosis.consent.quotes')" :help="__('diagnosis.consent.quotes_help')" />
            @endif

            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.button type="submit" size="lg" icon-right="arrow-right" :disabled="! $answersConsent">{{ __('diagnosis.invitation.start') }}</x-ui.button>
                    <x-ui.button tone="ghost" :href="route('client.home', $main->diagnosis->organization)">{{ __('diagnosis.invitation.later') }}</x-ui.button>
                </div>
                @unless ($answersConsent)
                    <p class="text-sm text-grey-700">{{ __('diagnosis.invitation.consent_needed') }}</p>
                @endunless
            </div>
        </x-ui.card>
    @endif
</div>
