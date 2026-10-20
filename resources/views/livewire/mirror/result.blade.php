{{-- ET3 · the values, ET5 · the full picture (06 · 6.1, 6.2). --}}
@php
    $status = $session->status;
    $recordUrl = $isPublic ? route('mirror.record') : route('client.mirror.record', $organization);
    // The public page has no header title, the customer layout already has the h1.
    $heading = $isPublic ? 'h1' : 'h2';
    $headingClass = $isPublic ? 'text-3xl font-extrabold uppercase' : 'text-xl font-semibold';
    $tile = 'flex cursor-pointer items-start gap-3 rounded-field border border-grey-350 bg-white p-4 transition-colors duration-200 hover:border-grey-500 has-checked:border-charcoal has-checked:bg-yellow-pale has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-coral';
@endphp

<div class="mx-auto flex max-w-xl flex-col gap-6 py-4">
    <x-ui.flash />

    @if ($isExpired)
        <x-ui.card padding="lg">{{ __('mirror.expired') }}</x-ui.card>
    @elseif ($status === \App\Enums\MirrorStatus::Processing)
        {{-- The poll sits here, so it also starts after "Újra próbálom" --}}
        <x-ui.card wire:key="processing" wire:poll.3s padding="lg" class="flex flex-col items-center gap-4 text-center" role="status">
            <x-megis.symbol name="growth-rings" class="size-16 animate-pulse text-charcoal" />
            <p class="text-xl font-semibold">{{ __('mirror.processing') }}</p>
            <p class="text-grey-700">{{ __('mirror.processing_hint') }}</p>
        </x-ui.card>
    @elseif ($status === \App\Enums\MirrorStatus::Failed)
        <x-ui.card wire:key="failed" padding="lg" class="flex flex-col items-start gap-4" role="alert">
            <p class="text-lg font-semibold">{{ __('mirror.failed') }}</p>
            @if ($canRetry && ! $retryLimited)
                <p class="text-grey-700">{{ __('mirror.failed_kept') }}</p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button icon="refresh" wire:click="retry">{{ __('mirror.try_again') }}</x-ui.button>
                    <x-ui.button tone="ghost" :href="$recordUrl">{{ __('mirror.start_over') }}</x-ui.button>
                </div>
            @else
                @if ($retryLimited)
                    <p class="text-grey-700">{{ __('mirror.retry_limited') }}</p>
                @endif
                <x-ui.button :href="$recordUrl">{{ $retryLimited ? __('mirror.start_over') : __('mirror.try_again') }}</x-ui.button>
            @endif
        </x-ui.card>
    @elseif ($step === 'values')
        {{-- ET3 · the values with quotes, no number, no rating --}}
        <div class="flex flex-col gap-1">
            <{{ $heading }} class="{{ $headingClass }}">{{ __('mirror.values_title') }}</{{ $heading }}>
            <p class="text-lg">{{ __('mirror.values_lead') }}</p>
            <p class="text-sm text-grey-700">{{ __('mirror.auto_with_quotes') }}</p>
        </div>

        @forelse ($groups as $group)
            <x-ui.card as="article" :tone="$group->excluded ? 'paper' : 'white'" wire:key="group-{{ md5($group->key) }}" class="flex flex-col gap-2">
                <div class="flex items-start justify-between gap-3">
                    <h3 @class(['pt-2 text-xl font-semibold', 'text-grey-700 line-through' => $group->excluded])>{{ $group->name }}</h3>
                    <x-ui.button
                        tone="outline"
                        size="sm"
                        wire:click="toggle(@js($group->key))"
                        aria-pressed="{{ $group->excluded ? 'true' : 'false' }}"
                        aria-label="{{ $group->excluded ? __('mirror.mine_again_label', ['name' => $group->name]) : __('mirror.not_mine_label', ['name' => $group->name]) }}"
                    >{{ $group->excluded ? __('mirror.mine_again') : __('mirror.not_mine') }}</x-ui.button>
                </div>
                <ul class="flex flex-col gap-1 text-grey-700">
                    @foreach ($group->quotes as $quote)
                        <li>„{{ $quote }}”</li>
                    @endforeach
                </ul>
            </x-ui.card>
        @empty
            {{-- Read but nothing heard: the same text again would not help, a new one might --}}
            <x-ui.empty-state symbol="spiral">
                {{ __('mirror.no_values') }}
                <x-slot:action>
                    <x-ui.button :href="$recordUrl">{{ __('mirror.try_again') }}</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @endforelse

        @if ($groups->isNotEmpty())
            <div>
                <x-ui.button size="lg" icon-right="arrow-right" wire:click="showFull">{{ __('mirror.continue') }}</x-ui.button>
            </div>
        @endif
    @elseif ($step === 'full')
        {{-- ET5 · the full picture --}}
        <div class="flex flex-col gap-2">
            <x-ui.button tone="ghost" size="sm" icon="chevron-left" class="-ml-1.5 self-start" wire:click="backToValues">{{ __('mirror.back_to_values') }}</x-ui.button>
            <{{ $heading }} class="{{ $headingClass }}">{{ __('mirror.full_title') }}</{{ $heading }}>
            <p class="text-grey-700">{{ $keptGroups->pluck('name')->implode(' · ') }}</p>
        </div>

        <div class="on-dark flex flex-col items-center gap-4 rounded-card bg-charcoal p-6 text-center text-cream">
            <p class="text-lg font-semibold">{{ __('mirror.vision') }}</p>
            <x-megis.symbol name="tree-of-life" class="size-24 text-yellow" />
            <p class="text-sm tracking-wide uppercase">{{ __('mirror.your_values') }}</p>
            <ul class="flex flex-wrap justify-center gap-2">
                @foreach ($keptGroups as $group)
                    <li class="rounded-pill bg-cream px-3 py-1 text-sm font-semibold text-charcoal">{{ $group->name }}</li>
                @endforeach
            </ul>
        </div>

        <fieldset class="flex flex-col gap-3">
            <legend class="mb-2 text-xl font-semibold">{{ __('mirror.curious') }}</legend>
            @foreach (\App\Models\MirrorSession::CURIOSITY as $key)
                <label class="{{ $tile }}">
                    <input type="checkbox" value="{{ $key }}" wire:model="curiosity" class="mt-0.5 size-5 shrink-0 accent-charcoal focus-visible:outline-none">
                    <span>
                        <span class="block font-semibold">{{ __('mirror.curiosity.'.$key.'.question') }}</span>
                        <span class="block text-sm text-grey-700">{{ __('mirror.curiosity.'.$key.'.hint') }}</span>
                    </span>
                </label>
            @endforeach
        </fieldset>

        @if ($isPublic)
            <x-ui.card as="form" wire:submit="createAccount(false)" class="flex flex-col gap-4">
                <p class="text-lg font-semibold">{{ __('mirror.see_answers') }}</p>
                <p class="text-sm text-grey-700">{{ __('mirror.promise') }} {{ __('mirror.and_more', ['count' => $otherValues]) }}</p>

                <x-form.input name="email" type="email" wire:model="email" :label="__('mirror.email')" autocomplete="email" required />

                {{-- Its label carries the two links, so it is built here; it looks like x-form.checkbox. --}}
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-start gap-3">
                        <input id="mirror-consent" type="checkbox" wire:model.live="consent" class="mt-0.5 size-5 shrink-0 accent-charcoal" @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror>
                        <label for="mirror-consent">
                            {{ __('mirror.consent_before') }}
                            @if ($termsUrl)<a href="{{ $termsUrl }}" target="_blank" rel="noopener" class="font-semibold">{{ __('mirror.consent_terms') }}<span class="sr-only"> ({{ __('ui.new_tab') }})</span></a>@else{{ __('mirror.consent_terms') }}@endif{{ __('mirror.consent_middle') }}
                            @if ($privacyUrl)<a href="{{ $privacyUrl }}" target="_blank" rel="noopener" class="font-semibold">{{ __('mirror.consent_privacy') }}<span class="sr-only"> ({{ __('ui.new_tab') }})</span></a>@else{{ __('mirror.consent_privacy') }}@endif
                        </label>
                    </div>
                    <x-form.error name="consent" id="consent" class="pl-8" />
                </div>

                <x-turnstile model="turnstileToken" />

                <div class="flex flex-col gap-2">
                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit" size="lg" :disabled="! $consent">{{ __('mirror.create_account') }}</x-ui.button>
                        @if ($bookingUrl)
                            <x-ui.button
                                tone="outline"
                                size="lg"
                                :href="$bookingUrl"
                                target="_blank"
                                rel="noopener"
                                :aria-disabled="$consent ? null : 'true'"
                                x-on:click="if (! $wire.consent) { $event.preventDefault(); return; } $wire.createAccount(true)"
                            >{{ __('mirror.book') }}<span class="sr-only"> ({{ __('ui.new_tab') }})</span></x-ui.button>
                        @endif
                    </div>
                    @unless ($consent)
                        <p class="text-sm text-grey-700">{{ __('mirror.consent_needed') }}</p>
                    @endunless
                </div>
                @if ($bookingUrl)
                    <p class="text-sm text-grey-700">{{ __('mirror.book_help') }}</p>
                @endif
            </x-ui.card>
        @else
            <div class="flex flex-wrap gap-3">
                <x-ui.button size="lg" wire:click="finish">{{ __('mirror.finish') }}</x-ui.button>
                @if ($bookingUrl)
                    <x-ui.button tone="outline" size="lg" :href="$bookingUrl" target="_blank" rel="noopener" x-on:click="$wire.book()">{{ __('mirror.book') }}<span class="sr-only"> ({{ __('ui.new_tab') }})</span></x-ui.button>
                @endif
            </div>
        @endif
    @elseif ($step === 'sent')
        <x-ui.card padding="lg" class="flex flex-col gap-3" role="status">
            <{{ $heading }} class="{{ $headingClass }}">{{ __('mirror.check_mail') }}</{{ $heading }}>
            <p>{{ __('mirror.check_mail_lead', ['email' => $session->email]) }}</p>
        </x-ui.card>
    @elseif ($step === 'existing')
        {{-- An existing account signs in here, the mirror is not lost (06 · 6.1) --}}
        <x-ui.card padding="lg" class="flex flex-col gap-4">
            <p class="text-lg font-semibold">{{ __('mirror.existing') }}</p>

            <x-ui.segmented :label="__('mirror.existing')">
                <x-ui.segment :selected="$loginMode === 'password'" wire:click="$set('loginMode', 'password')">{{ __('mirror.login_password') }}</x-ui.segment>
                <x-ui.segment :selected="$loginMode === 'code'" wire:click="$set('loginMode', 'code')">{{ __('mirror.login_code') }}</x-ui.segment>
            </x-ui.segmented>

            @if ($loginMode === 'password')
                <form wire:submit="loginWithPassword" class="flex flex-col gap-4">
                    <x-form.input name="password" type="password" wire:model="password" :label="__('mirror.password')" autocomplete="current-password" required />
                    <x-turnstile model="turnstileToken" />
                    <div><x-ui.button type="submit" data-focus-errors>{{ __('mirror.login') }}</x-ui.button></div>
                </form>
            @elseif (! $codeSent)
                <form wire:submit="sendCode" class="flex flex-col gap-4">
                    <x-turnstile model="turnstileToken" />
                    <div><x-ui.button type="submit" icon="mail">{{ __('mirror.login_code') }}</x-ui.button></div>
                </form>
            @else
                <form wire:submit="loginWithCode" class="flex flex-col gap-4">
                    <p class="text-sm">{{ __('mirror.code_sent', ['email' => $session->email]) }}</p>
                    <x-form.input name="code" wire:model="code" :label="__('mirror.code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" class="max-w-48 tracking-[0.3em] tabular-nums" required />
                    <div><x-ui.button type="submit" data-focus-errors>{{ __('mirror.login') }}</x-ui.button></div>
                </form>
            @endif
        </x-ui.card>
    @endif
</div>
