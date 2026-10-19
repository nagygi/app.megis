{{-- ET3 · the values, ET5 · the full picture (06 · 6.1, 6.2). --}}
@php($status = $session->status)

<div class="mx-auto flex max-w-xl flex-col gap-6 py-4">
    <x-ui.flash />

    @if ($isExpired)
        <p class="rounded-card bg-white p-6">{{ __('mirror.expired') }}</p>
    @elseif ($status === \App\Enums\MirrorStatus::Processing)
        {{-- The poll sits here, so it also starts after "Újra próbálom" --}}
        <div wire:key="processing" wire:poll.3s class="flex flex-col items-center gap-4 rounded-card bg-white p-8 text-center" role="status">
            <x-megis.symbol name="growth-rings" class="size-16 animate-spin text-coral [animation-duration:6s]" />
            <p class="text-xl font-semibold">{{ __('mirror.processing') }}</p>
            <p class="text-grey-700">{{ __('mirror.processing_hint') }}</p>
        </div>
    @elseif ($status === \App\Enums\MirrorStatus::Failed)
        @php($recordUrl = $isPublic ? route('mirror.record') : route('client.mirror.record', $organization))
        <div wire:key="failed" class="flex flex-col items-start gap-4 rounded-card bg-white p-6" role="alert">
            <p class="text-lg font-semibold">{{ __('mirror.failed') }}</p>
            @if ($canRetry && ! $retryLimited)
                <p class="text-grey-700">{{ __('mirror.failed_kept') }}</p>
                <x-ui.button wire:click="retry">{{ __('mirror.try_again') }}</x-ui.button>
                <a href="{{ $recordUrl }}" class="text-sm font-semibold underline">{{ __('mirror.start_over') }}</a>
            @else
                @if ($retryLimited)
                    <p class="text-grey-700">{{ __('mirror.retry_limited') }}</p>
                @endif
                <x-ui.button :href="$recordUrl">{{ $retryLimited ? __('mirror.start_over') : __('mirror.try_again') }}</x-ui.button>
            @endif
        </div>
    @elseif ($step === 'values')
        {{-- ET3 · the values with quotes, no number, no rating --}}
        <div class="flex flex-col gap-1">
            <h1 class="text-3xl font-extrabold uppercase">{{ __('mirror.values_title') }}</h1>
            <p class="text-lg">{{ __('mirror.values_lead') }}</p>
            <p class="text-sm text-grey-600">{{ __('mirror.auto_with_quotes') }}</p>
        </div>

        @forelse ($groups as $group)
            <article wire:key="group-{{ md5($group->key) }}" @class(['flex flex-col gap-2 rounded-card bg-white p-5', 'opacity-60' => $group->excluded])>
                <div class="flex items-start justify-between gap-3">
                    <h2 @class(['text-xl font-semibold', 'line-through' => $group->excluded])>{{ $group->name }}</h2>
                    <button
                        type="button"
                        wire:click="toggle(@js($group->key))"
                        class="shrink-0 rounded-pill border border-charcoal px-3 py-1 text-sm"
                        aria-pressed="{{ $group->excluded ? 'true' : 'false' }}"
                        aria-label="{{ $group->excluded ? __('mirror.mine_again_label', ['name' => $group->name]) : __('mirror.not_mine_label', ['name' => $group->name]) }}"
                    >{{ $group->excluded ? __('mirror.mine_again') : __('mirror.not_mine') }}</button>
                </div>
                <ul class="flex flex-col gap-1">
                    @foreach ($group->quotes as $quote)
                        <li class="text-grey-700">„{{ $quote }}”</li>
                    @endforeach
                </ul>
            </article>
        @empty
            <p class="rounded-card bg-white p-6">{{ __('mirror.no_values') }}</p>
        @endforelse

        @if ($groups->isNotEmpty())
            <div>
                <x-ui.button size="lg" wire:click="showFull">{{ __('mirror.continue') }}</x-ui.button>
            </div>
        @endif
    @elseif ($step === 'full')
        {{-- ET5 · the full picture --}}
        <div class="flex flex-col gap-2">
            <button type="button" wire:click="backToValues" class="self-start text-sm font-semibold underline">‹ {{ __('mirror.back_to_values') }}</button>
            <h1 class="text-3xl font-extrabold uppercase">{{ __('mirror.full_title') }}</h1>
            <p class="text-grey-700">{{ $keptGroups->pluck('name')->implode(' · ') }}</p>
        </div>

        <div class="flex flex-col items-center gap-4 rounded-card bg-charcoal p-6 text-center text-cream on-dark">
            <p class="text-lg font-semibold">{{ __('mirror.vision') }}</p>
            <x-megis.symbol name="tree-of-life" class="size-24 text-yellow" />
            <p class="text-sm uppercase tracking-wide">{{ __('mirror.your_values') }}</p>
            <ul class="flex flex-wrap justify-center gap-2">
                @foreach ($keptGroups as $group)
                    <li class="rounded-pill bg-cream px-3 py-1 text-sm font-semibold text-charcoal">{{ $group->name }}</li>
                @endforeach
            </ul>
        </div>

        <fieldset class="flex flex-col gap-3">
            <legend class="mb-2 text-xl font-semibold">{{ __('mirror.curious') }}</legend>
            @foreach (\App\Models\MirrorSession::CURIOSITY as $key)
                <label class="flex items-start gap-3 rounded-card bg-white p-4">
                    <input type="checkbox" value="{{ $key }}" wire:model="curiosity" class="mt-1 size-5 accent-charcoal">
                    <span>
                        <span class="block font-semibold">{{ __('mirror.curiosity.'.$key.'.question') }}</span>
                        <span class="block text-sm text-grey-700">{{ __('mirror.curiosity.'.$key.'.hint') }}</span>
                    </span>
                </label>
            @endforeach
        </fieldset>

        @if ($isPublic)
            <form wire:submit="createAccount(false)" class="flex flex-col gap-4 rounded-card bg-white p-5">
                <p class="text-lg font-semibold">{{ __('mirror.see_answers') }}</p>
                <p class="text-sm text-grey-700">{{ __('mirror.promise') }} {{ __('mirror.and_more', ['count' => $otherValues]) }}</p>

                <x-form.input name="email" type="email" wire:model="email" :label="__('mirror.email')" autocomplete="email" required />

                <div class="flex flex-col gap-1">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" wire:model.live="consent" class="mt-1 size-5 accent-charcoal" aria-describedby="consent-error">
                        <span>
                            {{ __('mirror.consent_before') }}
                            @if ($termsUrl)<a href="{{ $termsUrl }}" target="_blank" rel="noopener" class="font-semibold">{{ __('mirror.consent_terms') }}</a>@else{{ __('mirror.consent_terms') }}@endif{{ __('mirror.consent_middle') }}
                            @if ($privacyUrl)<a href="{{ $privacyUrl }}" target="_blank" rel="noopener" class="font-semibold">{{ __('mirror.consent_privacy') }}</a>@else{{ __('mirror.consent_privacy') }}@endif
                        </span>
                    </label>
                    <x-form.error name="consent" id="consent" />
                </div>

                <x-turnstile model="turnstileToken" />

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit" size="lg" :disabled="! $consent">{{ __('mirror.create_account') }}</x-ui.button>
                    @if ($bookingUrl)
                        <a
                            href="{{ $bookingUrl }}"
                            target="_blank"
                            rel="noopener"
                            x-on:click="if (! $wire.consent) { $event.preventDefault(); return; } $wire.createAccount(true)"
                            @class([
                                'press inline-flex items-center justify-center rounded-pill border-[1.5px] border-charcoal px-7 py-3.5 text-lg font-semibold no-underline',
                                'pointer-events-none border-grey-300 text-grey-600' => ! $consent,
                                'text-charcoal hover:bg-charcoal/5' => $consent,
                            ])
                            @if (! $consent) aria-disabled="true" @endif
                        >{{ __('mirror.book') }}</a>
                    @endif
                </div>
                @if ($bookingUrl)
                    <p class="text-sm text-grey-600">{{ __('mirror.book_help') }}</p>
                @endif
            </form>
        @else
            <div class="flex flex-wrap gap-3">
                <x-ui.button size="lg" wire:click="finish">{{ __('mirror.finish') }}</x-ui.button>
                @if ($bookingUrl)
                    <a href="{{ $bookingUrl }}" target="_blank" rel="noopener" x-on:click="$wire.book()" class="press inline-flex items-center justify-center rounded-pill border-[1.5px] border-charcoal px-7 py-3.5 text-lg font-semibold text-charcoal no-underline hover:bg-charcoal/5">{{ __('mirror.book') }}</a>
                @endif
            </div>
        @endif
    @elseif ($step === 'sent')
        <div class="flex flex-col gap-3 rounded-card bg-white p-6" role="status">
            <h1 class="text-2xl font-semibold">{{ __('mirror.check_mail') }}</h1>
            <p>{{ __('mirror.check_mail_lead', ['email' => $session->email]) }}</p>
        </div>
    @elseif ($step === 'existing')
        {{-- An existing account signs in here, the mirror is not lost (06 · 6.1) --}}
        <div class="flex flex-col gap-4 rounded-card bg-white p-6">
            <p class="text-lg font-semibold">{{ __('mirror.existing') }}</p>

            <div class="flex flex-wrap gap-2" role="group">
                <button type="button" wire:click="$set('loginMode', 'password')" @class(['rounded-pill border px-3 py-1 text-sm', 'bg-charcoal text-cream' => $loginMode === 'password']) aria-pressed="{{ $loginMode === 'password' ? 'true' : 'false' }}">{{ __('mirror.login_password') }}</button>
                <button type="button" wire:click="$set('loginMode', 'code')" @class(['rounded-pill border px-3 py-1 text-sm', 'bg-charcoal text-cream' => $loginMode === 'code']) aria-pressed="{{ $loginMode === 'code' ? 'true' : 'false' }}">{{ __('mirror.login_code') }}</button>
            </div>

            @if ($loginMode === 'password')
                <form wire:submit="loginWithPassword" class="flex flex-col gap-4">
                    <x-form.input name="password" type="password" wire:model="password" :label="__('mirror.password')" autocomplete="current-password" required />
                    <x-turnstile model="turnstileToken" />
                    <div><x-ui.button type="submit">{{ __('mirror.login') }}</x-ui.button></div>
                </form>
            @elseif (! $codeSent)
                <form wire:submit="sendCode" class="flex flex-col gap-4">
                    <x-turnstile model="turnstileToken" />
                    <div><x-ui.button type="submit">{{ __('mirror.login_code') }}</x-ui.button></div>
                </form>
            @else
                <form wire:submit="loginWithCode" class="flex flex-col gap-4">
                    <p class="text-sm">{{ __('mirror.code_sent', ['email' => $session->email]) }}</p>
                    <x-form.input name="code" wire:model="code" :label="__('mirror.code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required />
                    <div><x-ui.button type="submit">{{ __('mirror.login') }}</x-ui.button></div>
                </form>
            @endif
        </div>
    @endif
</div>
