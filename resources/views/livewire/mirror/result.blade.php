{{-- ET3 · the values, ET4 · one value deepened (NM-06), ET5 · the full picture (06 · 6.1, 6.2). --}}
@php
    $status = $session->status;
    $recordUrl = $isPublic ? route('mirror.record') : route('client.mirror.record', $organization);
    // The public page has no header title, the customer layout already has the h1.
    $heading = $isPublic ? 'h1' : 'h2';
    $headingClass = $isPublic ? 'text-3xl font-extrabold uppercase' : 'text-xl font-semibold';
    $label = 'text-xs font-semibold tracking-wide text-grey-700 uppercase';
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
            <p class="text-xl font-semibold"><x-ui.working-text :text="__('mirror.processing')" /></p>
            <p class="text-grey-700">{{ __('mirror.processing_hint') }}</p>
            @if ($isPublic && $session->awaitsConfirmation())
                <p class="text-sm text-grey-700">{{ __('mirror.processing_mail', ['email' => $session->email]) }}</p>
            @endif
            @if ($stories !== [])
                {{-- Short stories from the life of the mégis while the mirror is read; they turn by themselves and outlive the poll (06 · ET2) --}}
                <figure
                    wire:ignore
                    x-data="{ index: 0, stories: {{ \Illuminate\Support\Js::from($stories) }} }"
                    x-init="if (stories.length > 1) setInterval(() => index = (index + 1) % stories.length, 12000)"
                    class="mt-2 flex w-full flex-col gap-2 rounded-card bg-yellow-pale p-5 text-left"
                >
                    <figcaption class="text-xs font-semibold tracking-wide text-grey-700 uppercase">{{ __('mirror.stories_title') }}</figcaption>
                    <p x-text="stories[index]">{{ $stories[0] }}</p>
                </figure>
            @endif
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
    @elseif ($step === 'value' && $current)
        {{--
            ET4 · one value in the user's own words: meaning, signs, the 1 and the 6, the quotes (NM-06).
            The user approves it ("Jóváhagyom", back to the list) or writes any part of it, the name too ("Átírom").
        --}}
        @php
            $meaningText = $insight?->meaning ?? ($insightPending ? null : ($libraryValue?->meanings[0] ?? null));
            $meaningLabel = match (true) {
                $current->edited => __('mirror.own_definition'),
                filled($insight?->meaning) => __('mirror.coach_label'),
                default => __('mirror.library_meaning'),
            };
            $low = $insight?->low_example ?? ($insightPending ? null : ($libraryValue?->low_examples[0] ?? null));
            $high = $insight?->high_example ?? ($insightPending ? null : ($libraryValue?->high_examples[0] ?? null));
        @endphp
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between gap-3">
                <x-ui.button tone="ghost" size="sm" icon="chevron-left" class="-ml-1.5" wire:click="showValues">{{ __('mirror.back_to_values') }}</x-ui.button>
                <p class="text-xs font-semibold tracking-wider text-grey-700 uppercase">{{ __('mirror.deepen_position', ['name' => $current->name, 'position' => $position, 'count' => $keptGroups->count()]) }}</p>
            </div>
            <div class="flex items-start gap-3">
                <x-megis.symbol name="root" class="mt-0.5 size-10 shrink-0 text-coral" />
                <div class="flex flex-col gap-1">
                    <{{ $heading }} class="{{ $headingClass }}">{{ $current->name }}</{{ $heading }}>
                    @if ($current->edited)
                        <p class="flex flex-wrap items-center gap-2 text-sm text-grey-700">
                            <x-ui.badge tone="yellow">{{ __('mirror.own_definition') }}</x-ui.badge>
                            @if ($current->isRenamed())
                                {{ __('mirror.originally', ['name' => $current->originalName]) }}
                            @endif
                        </p>
                    @endif
                    <p class="text-grey-700">{{ __('mirror.deepen_lead') }}</p>
                </div>
            </div>
        </div>

        @if ($rewriting)
            {{-- Every part in the user's own words; the Értéktár's value does not change (06 · ET4) --}}
            <x-ui.card as="form" wire:submit="saveRewrite" class="flex flex-col gap-4">
                <p class="text-sm text-grey-700">{{ __('mirror.edit.lead') }}</p>
                <x-form.input name="ownName" wire:model="ownName" :label="__('mirror.edit.name')" maxlength="{{ \App\Services\Mirror\MirrorInsightService::NAME_MAX }}" required />
                <x-form.textarea name="meaning" wire:model="meaning" rows="3" :label="__('mirror.edit.meaning')" maxlength="{{ \App\Services\Mirror\MirrorInsightService::MEANING_MAX }}" required />
                <x-form.textarea name="signs" wire:model="signs" rows="4" :label="__('mirror.edit.signs')" :help="__('mirror.edit.signs_help', ['count' => \App\Services\Mirror\MirrorInsightService::SIGNS_MAX])" />
                <x-form.textarea name="lowExample" wire:model="lowExample" rows="2" :label="__('mirror.edit.low')" maxlength="{{ \App\Services\Mirror\MirrorInsightService::MEANING_MAX }}" />
                <x-form.textarea name="highExample" wire:model="highExample" rows="2" :label="__('mirror.edit.high')" maxlength="{{ \App\Services\Mirror\MirrorInsightService::MEANING_MAX }}" />
                <div class="flex flex-wrap gap-2">
                    <x-ui.button type="submit" data-focus-errors>{{ __('mirror.edit.save') }}</x-ui.button>
                    <x-ui.button tone="outline" wire:click="cancelRewrite">{{ __('ui.cancel') }}</x-ui.button>
                </div>
            </x-ui.card>
        @else
            @if ($insightPending && blank($insight?->meaning))
                <section wire:key="coach-pending" wire:poll.3s class="flex items-center gap-3 rounded-card bg-grey-100 p-5" role="status">
                    <x-megis.symbol name="spiral" class="size-8 shrink-0 animate-pulse text-coral" />
                    <p><x-ui.working-text :text="__('mirror.coach_pending')" /></p>
                </section>
            @elseif (filled($meaningText))
                <section wire:key="meaning-{{ md5($current->key) }}" aria-labelledby="meaning-title" class="flex flex-col gap-3 rounded-card bg-grey-100 p-5">
                    <p id="meaning-title" class="flex items-center gap-2 {{ $label }} text-charcoal">
                        <x-megis.symbol name="spiral" class="size-5 shrink-0 text-coral" />
                        {{ $meaningLabel }}
                    </p>
                    <p class="text-lg leading-snug font-semibold">{{ $meaningText }}</p>
                </section>
            @endif

            @if (filled($insight?->signs))
                <section class="flex flex-col gap-2">
                    <h3 class="{{ $label }}">{{ __('mirror.signs') }}</h3>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($insight->signs as $sign)
                            <li><x-ui.chip>{{ $sign }}</x-ui.chip></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($low || $high)
                {{-- A Likert-like scale: the two ends said in words, not only by the number (06 · ET4) --}}
                <section class="flex flex-col gap-2">
                    <h3 class="{{ $label }}">{{ __('mirror.scale_ends') }}</h3>
                    <p class="text-sm text-grey-700">{{ __('mirror.scale_lead') }}</p>
                    <dl class="flex flex-col">
                        @if ($low)
                            <div class="flex gap-4 border-b border-grey-250 py-3">
                                <dt class="w-4 shrink-0 text-lg font-extrabold text-coral-ink">1</dt>
                                <dd class="flex flex-col gap-0.5">
                                    <span class="text-xs font-semibold tracking-wide text-coral-ink uppercase">{{ __('mirror.scale_low') }}</span>
                                    <span>{{ $low }}</span>
                                </dd>
                            </div>
                        @endif
                        @if ($high)
                            <div class="flex gap-4 py-3">
                                <dt class="w-4 shrink-0 text-lg font-extrabold text-green">6</dt>
                                <dd class="flex flex-col gap-0.5">
                                    <span class="text-xs font-semibold tracking-wide text-green uppercase">{{ __('mirror.scale_high') }}</span>
                                    <span>{{ $high }}</span>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </section>
            @endif
        @endif

        <section class="flex flex-col gap-2">
            <h3 class="{{ $label }}">{{ __('mirror.built_from') }}</h3>
            @foreach ($current->quotes as $quote)
                <figure class="flex flex-col gap-1 rounded-card bg-grey-100 p-4">
                    <blockquote>„{{ $quote }}”</blockquote>
                    <figcaption class="text-xs text-grey-700">{{ $fromRecording ? __('mirror.your_recording') : __('mirror.your_text') }}</figcaption>
                </figure>
            @endforeach
        </section>

        {{-- One way through the values: from the list into each, approved, back to the list (06 · ET3) --}}
        @unless ($rewriting)
            <div class="flex flex-col items-start gap-3">
                @if ($current->approved)
                    <p class="flex items-center gap-2 font-semibold text-green" role="status">
                        <x-megis.icon name="check" />
                        {{ $current->edited ? __('mirror.edited_status') : __('mirror.approved_status') }}
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <x-ui.button size="lg" icon="chevron-left" wire:click="showValues">{{ __('mirror.back_to_values') }}</x-ui.button>
                        <x-ui.button tone="outline" size="lg" wire:click="startRewrite">{{ __('mirror.edit.open') }}</x-ui.button>
                    </div>
                @else
                    <div class="flex flex-wrap gap-3">
                        <x-ui.button size="lg" icon="check" wire:click="approve" :disabled="$insightPending">{{ __('mirror.approve') }}</x-ui.button>
                        <x-ui.button tone="outline" size="lg" wire:click="startRewrite" :disabled="$insightPending">{{ __('mirror.edit.open') }}</x-ui.button>
                    </div>
                    <p class="text-sm text-grey-700">{{ $insightPending ? __('mirror.approve_wait') : __('mirror.approve_hint') }}</p>
                @endif
            </div>
        @endunless
    @elseif ($step === 'full')
        {{--
            ET5 · the full picture: the tree around the values, and what the user is curious about.
            An invited member's first mirror ends their part (06 · 6.2, 2026-11-27): their values,
            that there is nothing more to do, the organization as a tree, back to the home.
        --}}
        <div class="flex flex-col gap-3">
            <x-ui.button tone="ghost" size="sm" icon="chevron-left" class="-ml-1.5 self-start" wire:click="showValues">{{ __('mirror.back_to_values') }}</x-ui.button>
            <div class="flex items-start gap-3">
                <x-megis.symbol name="tree-of-life" class="mt-0.5 size-10 shrink-0 text-coral" />
                <div class="flex flex-col gap-1">
                    <{{ $heading }} class="{{ $headingClass }}">{{ $waitsForTeam ? __('mirror.full_title_waiting') : __('mirror.full_title') }}</{{ $heading }}>
                    <p class="text-grey-700">{{ $keptGroups->pluck('name')->implode(' · ') }}</p>
                </div>
            </div>
        </div>

        @if ($waitsForTeam)
            <x-client.mirror-waiting>
                <a href="{{ route('client.sample-report', $organization) }}" class="mt-1 self-start font-semibold">{{ __('sample_report.link') }}</a>
            </x-client.mirror-waiting>
        @else
            <div class="on-dark relative overflow-hidden rounded-card bg-charcoal p-6 text-cream">
                <x-megis.symbol name="root" class="absolute -top-10 -right-12 size-28 text-yellow" />
                <p class="relative pr-10 text-2xl leading-tight font-extrabold uppercase">
                    {{ __('mirror.vision.line1') }}<br>
                    {{ __('mirror.vision.line2') }}<br>
                    <span class="text-yellow">{{ __('mirror.vision.line3') }}</span>
                </p>
            </div>
        @endif

        {{-- The values are above already: an invited member's tree is the organization's --}}
        @include('livewire.mirror.partials.tree-circle', ['names' => $keptGroups->pluck('name'), 'center' => $waitsForTeam ? __('mirror.tree_organization') : null])

        @unless ($isPublic)
            {{-- The tree's parts are told in the Bölcsességtár; it is behind the login, so not on the public page --}}
            <p class="-mt-2 text-sm">
                {{ __('mirror.tree_more') }}
                <a href="{{ route('client.wisdom.show', [$organization, 'kuldetesfa']) }}" class="font-semibold">{{ __('mirror.tree_more_link') }}</a>
            </p>
        @endunless

        {{-- An invited member already agreed with the mégis what it is about: no questions for them (06 · 6.2) --}}
        @if ($asksCuriosity)
            <fieldset class="flex flex-col gap-3">
                <legend class="mb-2 text-xl font-semibold">{{ __('mirror.curious') }}</legend>
                @foreach (\App\Models\MirrorSession::CURIOSITY as $key)
                    <label class="{{ $tile }}">
                        <input type="checkbox" value="{{ $key }}" wire:model.live="curiosity" class="mt-0.5 size-5 shrink-0 accent-charcoal focus-visible:outline-none">
                        <span class="flex-1">
                            <span class="block font-semibold">{{ __('mirror.curiosity.'.$key.'.question') }}</span>
                            <span class="block text-sm text-grey-700">{{ __('mirror.curiosity.'.$key.'.hint') }}</span>
                        </span>
                        <span class="flex shrink-0 gap-1 pt-0.5">
                            @foreach (\App\Models\MirrorSession::CURIOSITY_SYMBOLS[$key] as $symbol)
                                <x-megis.symbol :name="$symbol" class="size-5" />
                            @endforeach
                        </span>
                    </label>
                @endforeach
            </fieldset>

            {{-- "Elküldöm": the ticked questions go to the mégis by e-mail, once a mirror; sent, "Hamarosan" stands in its place --}}
            @if ($interestSent)
                <div class="on-dark -mt-2 flex items-center gap-4 rounded-card bg-charcoal p-5 text-cream" role="status">
                    <x-megis.symbol name="growth-rings" class="size-12 shrink-0 text-yellow" />
                    <div class="flex min-w-0 flex-col gap-1">
                        <p class="text-xl leading-tight font-extrabold text-yellow uppercase">{{ __('mirror.interest_sent_title') }}</p>
                        <p class="break-words">{{ $interestEmail ? __('mirror.interest_sent', ['email' => $interestEmail]) : __('mirror.interest_sent_plain') }}</p>
                    </div>
                </div>
            @else
                <div class="-mt-2 flex flex-col items-start gap-3">
                    <x-ui.button :tone="$isPublic ? 'dark' : 'outline'" icon="mail" wire:click="sendInterest" :disabled="$curiosity === []">{{ __('mirror.send_interest') }}</x-ui.button>
                    <x-form.error name="curiosity" id="curiosity" role="alert" />
                </div>
            @endif
        @endif

        @if (! $isPublic)
            <div>
                {{-- An invited member's first mirror goes back to the home: no step follows (26 · 26.4) --}}
                <x-ui.button size="lg" wire:click="finish">{{ $waitsForTeam ? __('mirror.finish_home') : __('mirror.finish') }}</x-ui.button>
            </div>
        @elseif ($session->awaitsConfirmation())
            {{-- The link went out with the mirror; confirmed, the mirror stays in the account (06 · 6.1) --}}
            <x-ui.card padding="lg" class="flex flex-col gap-3" role="status">
                <h2 class="text-lg font-semibold">{{ __('mirror.check_mail') }}</h2>
                <p>{{ __('mirror.check_mail_lead', ['email' => $session->email]) }}</p>
                <p class="text-sm text-grey-700">{{ __('mirror.promise', ['values' => $keptGroups->pluck('name')->implode(' · ')]) }} @if ($otherValues > 0){{ __('mirror.and_more', ['count' => $otherValues]) }}@endif</p>
            </x-ui.card>
        @elseif ($session->belongsToExistingAccount())
            {{-- An existing account signs in here, the mirror is not lost (06 · 6.1) --}}
            <x-ui.card padding="lg" class="flex flex-col gap-4">
                <h2 class="text-lg font-semibold">{{ __('mirror.existing') }}</h2>

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
        @elseif ($accountUrl)
            <x-ui.card padding="lg" class="flex flex-col items-start gap-3" role="status">
                <p class="text-lg font-semibold">{{ __('mirror.claimed') }}</p>
                <x-ui.button :href="$accountUrl" icon-right="arrow-right">{{ __('mirror.open_in_account') }}</x-ui.button>
            </x-ui.card>
        @endif
    @else
        {{-- ET3 · the values with quotes, no number, no rating; each opens, to be approved (06 · ET3) --}}
        <div class="flex items-start gap-3">
            <x-megis.symbol name="root" class="mt-0.5 size-10 shrink-0 text-coral" />
            <div class="flex flex-col gap-1">
                <{{ $heading }} class="{{ $headingClass }}">{{ __('mirror.values_title') }}</{{ $heading }}>
                <p class="text-lg">{{ __('mirror.values_lead') }}</p>
                <p class="text-sm text-grey-700">{{ __('mirror.auto_with_quotes') }}</p>
            </div>
        </div>

        @if ($keptGroups->isNotEmpty())
            <p class="flex flex-wrap items-baseline gap-x-2 text-sm">
                <span>{{ __('mirror.review_lead') }}</span>
                <span class="font-semibold">{{ __('mirror.approved_count', ['done' => $approvedCount, 'total' => $keptGroups->count()]) }}</span>
            </p>
        @endif

        @forelse ($groups as $group)
            @php
                $keptPosition = $group->excluded ? null : $keptGroups->search(fn ($kept) => $kept->key === $group->key) + 1;
            @endphp
            <x-ui.card as="article" :tone="$group->excluded ? 'paper' : 'white'" wire:key="group-{{ md5($group->key) }}" class="flex flex-col gap-2">
                <div class="flex items-start justify-between gap-3">
                    @if ($keptPosition)
                        <div class="flex min-w-0 flex-col items-start gap-1.5">
                            <h3 class="pt-1.5">
                                <button type="button" wire:click="showValue({{ $keptPosition }})" class="press flex items-center gap-1.5 text-left text-xl font-semibold">
                                    {{ $group->name }}
                                    <x-megis.icon name="chevron-right" />
                                    <span class="sr-only">{{ __('mirror.deepen_open') }}</span>
                                </button>
                            </h3>
                            <p class="flex flex-wrap items-center gap-2 text-sm text-grey-700">
                                @if ($group->approved)
                                    <x-ui.badge tone="green">{{ __('mirror.approved_badge') }}</x-ui.badge>
                                @else
                                    <x-ui.badge tone="neutral">{{ __('mirror.to_review_badge') }}</x-ui.badge>
                                @endif
                                @if ($group->edited)
                                    <x-ui.badge tone="yellow">{{ __('mirror.own_definition') }}</x-ui.badge>
                                @endif
                                @if ($group->isRenamed())
                                    <span>{{ __('mirror.originally', ['name' => $group->originalName]) }}</span>
                                @endif
                            </p>
                        </div>
                    @else
                        <h3 class="pt-2 text-xl font-semibold text-grey-700 line-through">{{ $group->name }}</h3>
                    @endif
                    {{-- @js is not compiled in a component's attribute, Js::from in an echo is --}}
                    <x-ui.button
                        tone="outline"
                        size="sm"
                        wire:click="toggle({{ \Illuminate\Support\Js::from($group->key) }})"
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
            {{-- Signed in, ET5 comes once every kept value is approved; the public mirror does not wait --}}
            @php($waitsForApproval = $requiresApproval && $approvedCount < $keptGroups->count())
            <div class="flex flex-col items-start gap-2">
                <x-ui.button size="lg" icon-right="arrow-right" wire:click="showFull" :disabled="$waitsForApproval">{{ __('mirror.continue') }}</x-ui.button>
                @if ($waitsForApproval)
                    <p class="text-sm text-grey-700">{{ __('mirror.approve_all_first') }}</p>
                @endif
                <x-form.error name="approval" id="approval" role="alert" />
            </div>
        @endif

        @if ($waitsForTeam)
            {{-- One mirror for an invited member: a new one replaces this one (06 · 6.2) --}}
            <div class="flex flex-col items-start gap-1 border-t border-grey-200 pt-4">
                <x-ui.button tone="ghost" size="sm" icon="refresh" wire:click="startOver" wire:confirm="{{ __('mirror.start_over_confirm') }}">{{ __('mirror.start_over_new') }}</x-ui.button>
                <p class="text-sm text-grey-700">{{ __('mirror.start_over_help') }}</p>
            </div>
        @endif
    @endif
</div>
