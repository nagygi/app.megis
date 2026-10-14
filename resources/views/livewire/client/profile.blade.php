<div class="flex max-w-xl flex-col gap-8">
    <x-ui.flash />

    <section class="flex flex-col gap-5" aria-labelledby="profile-me">
        <div class="flex items-center gap-4">
            <span class="grid size-14 place-items-center rounded-pill bg-charcoal text-lg font-bold text-cream" aria-hidden="true">{{ $user->initials }}</span>
            <div>
                <h2 id="profile-me" class="text-lg font-semibold">{{ $user->name }}</h2>
                @if ($roleLine !== '')
                    <p class="text-sm text-grey-700">{{ $roleLine }}</p>
                @endif
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="last_name" wire:model.blur="last_name" :label="__('auth_ui.registration.last_name')" autocomplete="family-name" required />
            <x-form.input name="first_name" wire:model.blur="first_name" :label="__('auth_ui.registration.first_name')" autocomplete="given-name" required />
        </div>
        <p class="text-sm text-green" role="status" aria-live="polite">
            @if ($nameSaved) {{ __('ui.saved') }} @endif
        </p>

        <div class="flex flex-col gap-1">
            <span class="text-sm font-medium">{{ __('auth_ui.email') }}</span>
            <span>{{ $user->email }}</span>
            <span class="text-sm text-grey-600">{{ __('client.profile.email_hint') }}</span>
        </div>
    </section>

    @if ($leaderMain)
        {{-- A diagnózisod (31 · 31.2) --}}
        <section class="flex flex-col gap-2 border-t border-grey-250 pt-6" aria-labelledby="profile-diagnosis">
            <h2 id="profile-diagnosis" class="text-lg font-semibold">{{ __('client.profile.diagnosis_title') }}</h2>
            <p>{{ __('client.profile.diagnosis_window', ['product' => $leaderMain->product_name, 'from' => ($leaderMain->starts_on ?? $leaderMain->running_at)?->format('m.d.') ?? '–', 'to' => $leaderMain->publishes_on->format('m.d.')]) }}</p>
            <p class="text-sm text-grey-700">{{ $stateLine }}</p>
            <p class="text-sm text-grey-700">{{ __('client.profile.contact', ['name' => $leaderMain->diagnosis->organization->contactAdmin?->name ?? '–']) }}</p>
            <a href="{{ route('client.diagnosis.ki-lat-mit', [$organization, $leaderMain->diagnosis]) }}" class="self-start text-sm font-semibold">{{ __('client.profile.visibility') }}</a>
        </section>
    @endif

    @if ($awaiting !== [])
        {{-- Rád vár (31 · 31.3) --}}
        <section class="flex flex-col gap-2" aria-labelledby="profile-awaiting">
            <h2 id="profile-awaiting" class="text-lg font-semibold">{{ __('client.profile.awaiting_title') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($awaiting as $row)
                    <li>
                        <a href="{{ $row['url'] }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
                            <span>{{ $row['label'] }}</span>
                            <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($people)
        {{-- Emberek (31 · 31.4) --}}
        <section class="flex flex-col gap-2" aria-labelledby="profile-people">
            <h2 id="profile-people" class="text-lg font-semibold">{{ __('client.profile.people_title') }}</h2>
            <p>{{ __('client.profile.people_count', $people) }}</p>
            <p class="text-sm text-grey-700">{{ __('client.profile.people_rule') }}</p>
            @if ($people['can_remind'] && $people['answered'] < $people['total'] && ! $isImpersonating)
                <x-ui.button tone="outline" size="sm" class="self-start" wire:click="remindPeers({{ $leaderMain->id }})">{{ __('client.profile.remind') }}</x-ui.button>
            @endif
        </section>
    @endif

    <section class="flex flex-col gap-5 border-t border-grey-250 pt-6" aria-labelledby="profile-password">
        <h2 id="profile-password" class="text-lg font-semibold">{{ __('client.profile.password_title') }}</h2>

        @if ($isImpersonating)
            <p class="rounded-card bg-yellow-pale p-4">{{ __('impersonation.forbidden') }}</p>
        @else
            @if ($passwordSaved)
                <p class="rounded-card bg-green-soft p-4 text-green" role="status">{{ __('client.profile.password_saved') }}</p>
            @endif

            <form wire:submit="savePassword" class="flex flex-col gap-5">
                @if ($hasPassword)
                    <p>{{ __('client.profile.password_lead_change') }}</p>
                    <x-form.input name="current_password" type="password" wire:model="current_password" :label="__('auth_ui.current_password')" autocomplete="current-password" required />
                @else
                    <p>{{ __('client.profile.password_lead_set') }}</p>
                    @if ($codeSent)
                        <p class="text-sm text-grey-700" role="status">{{ __('client.profile.code_sent', ['email' => $user->email]) }}</p>
                        <x-form.input name="code" wire:model="code" :label="__('client.profile.code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required />
                    @else
                        <x-ui.button tone="outline" class="self-start" wire:click="sendCode">{{ __('client.profile.send_code') }}</x-ui.button>
                    @endif
                @endif

                @if ($hasPassword || $codeSent)
                    <x-form.input
                        name="password"
                        type="password"
                        wire:model="password"
                        :label="__('auth_ui.new_password')"
                        :help="\App\Support\PasswordRules::hintFor($user)"
                        autocomplete="new-password"
                        required
                    />
                    <x-form.input name="password_confirmation" type="password" wire:model="password_confirmation" :label="__('auth_ui.password_confirmation')" autocomplete="new-password" required />

                    <x-ui.button type="submit" class="self-start">{{ __('client.profile.save_password') }}</x-ui.button>
                @endif
            </form>
        @endif
    </section>

    {{-- Naptár (09 · 9.5, 9.6; 31 · 31.5) --}}
    @if ($calendars->isNotEmpty())
        <section class="flex flex-col gap-3" aria-labelledby="calendar-title">
            <h2 id="calendar-title" class="text-lg font-semibold">{{ __('calendar.profile.title') }}</h2>
            @foreach ($calendars as $calendar)
                <div wire:key="calendar-{{ $calendar->id }}" class="flex flex-wrap items-center justify-between gap-3 rounded-card bg-white px-4 py-3">
                    <span>
                        <span class="block font-semibold">{{ $calendar->providerLabel() }} · {{ __('calendar.profile.connected', ['date' => $calendar->connected_at->format('m.d.')]) }}</span>
                        <span class="block text-sm text-grey-700">{{ __('calendar.profile.metadata_until', ['date' => $calendar->metadata_expires_at->format('m.d.')]) }}</span>
                        @if ($calendar->status === \App\Models\CalendarConnection::BROKEN)
                            <span class="block text-sm font-semibold text-coral-ink" role="status">{{ __('calendar.profile.broken') }}</span>
                        @endif
                    </span>
                    <x-ui.button tone="outline" size="sm" wire:click="disconnectCalendar({{ $calendar->id }})" wire:confirm="{{ __('calendar.profile.confirm_disconnect') }}">{{ __('calendar.profile.disconnect') }}</x-ui.button>
                </div>
            @endforeach
        </section>
    @endif

    {{-- Hozzájárulásaim (31 · 31.5, 31.6) --}}
    <section class="flex flex-col gap-3" aria-labelledby="consents-title">
        <h2 id="consents-title" class="text-lg font-semibold">{{ __('client.consents.title') }}</h2>

        @if ($acceptances->isNotEmpty())
            <ul class="flex flex-col gap-1 text-sm">
                @foreach ($acceptances as $acceptance)
                    <li>{{ $acceptance->version?->type?->label() }} · {{ __('client.consents.accepted_at', ['date' => $acceptance->accepted_at?->format('Y.m.d.')]) }}</li>
                @endforeach
            </ul>
        @endif

        @foreach ($consents as $mainConsents)
            @php($consentMain = $mainConsents->first()->mainDiagnosis)
            <div wire:key="consents-{{ $consentMain->id }}" class="flex flex-col gap-2 rounded-card bg-white px-4 py-3">
                <p class="font-semibold">{{ __('client.consents.diagnosis', ['subject' => $consentMain->subjectName()]) }}</p>
                <ul class="flex flex-col gap-2">
                    @foreach ($mainConsents->unique('kind') as $consent)
                        <li class="flex flex-wrap items-center justify-between gap-3">
                            <span>
                                <span class="block">{{ $consent->kind->label() }}</span>
                                <span class="block text-sm text-grey-700">
                                    {{ $consent->revoked_at ? __('client.consents.revoked_at', ['date' => $consent->revoked_at->format('Y.m.d.')]) : __('client.consents.given_at', ['date' => $consent->given_at->format('Y.m.d.')]) }}
                                </span>
                            </span>
                            @if (! $isImpersonating && $consentService->canRevoke($consent))
                                <x-ui.button
                                    tone="outline"
                                    size="sm"
                                    wire:click="revokeConsent({{ $consent->id }})"
                                    wire:confirm="{{ $consent->kind === \App\Enums\ConsentKind::Answers ? __('client.consents.confirm_answers', ['subject' => $consentMain->subjectName()]) : __('client.consents.confirm_quotes') }}"
                                >{{ __('client.consents.revoke') }}</x-ui.button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </section>

    {{-- Felvételeim (31 · 31.5, 06 · 6.6) --}}
    <section class="flex flex-col gap-3" aria-labelledby="recordings-title">
        <h2 id="recordings-title" class="text-lg font-semibold">{{ __('client.recordings.title') }}</h2>
        <p class="text-sm text-grey-700">{{ __('client.recordings.lead') }}</p>
        @if ($recordings->isEmpty())
            <p class="text-grey-600">{{ __('client.recordings.empty') }}</p>
        @else
            <ul class="flex flex-col gap-2">
                @foreach ($recordings as $recording)
                    <li wire:key="recording-{{ $recording->uuid }}" class="flex flex-wrap items-center justify-between gap-3 rounded-card bg-white px-4 py-3">
                        <span>
                            <span class="block font-semibold">{{ $recording->context->label() }}</span>
                            <span class="block text-sm text-grey-700">{{ $recording->created_at->format('Y.m.d.') }}@if ($recording->durationLabel()) · {{ $recording->source->label() }} · {{ $recording->durationLabel() }}@endif</span>
                        </span>
                        @unless ($isImpersonating)
                            <x-ui.button
                                tone="outline"
                                size="sm"
                                wire:click="deleteRecording('{{ $recording->uuid }}')"
                                wire:confirm="{{ __('client.recordings.confirm_delete') }}"
                                aria-label="{{ __('client.recordings.delete_label', ['date' => $recording->created_at->format('Y.m.d.')]) }}"
                            >{{ __('client.recordings.delete') }}</x-ui.button>
                        @endunless
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @unless ($isImpersonating)
        {{-- Adataim törlése (31 · 31.7) --}}
        <section class="flex flex-col gap-3 border-t border-grey-250 pt-6" aria-labelledby="profile-deletion">
            <h2 id="profile-deletion" class="text-lg font-semibold">{{ __('client.deletion.title') }}</h2>
            @if ($confirmingDeletion)
                <div class="flex flex-col gap-2 rounded-card bg-white p-4 text-sm">
                    <p>{{ __('client.deletion.what_goes') }}</p>
                    <p>{{ __('client.deletion.what_stays') }}</p>
                    <p>{{ __('client.deletion.when') }}</p>
                </div>
                <form wire:submit="deleteAccount" class="flex flex-col gap-3">
                    @if ($hasPassword)
                        <x-form.input name="deletionPassword" type="password" wire:model="deletionPassword" :label="__('client.profile.current_password')" autocomplete="current-password" required />
                    @else
                        @if ($deletionCodeSent)
                            <x-form.input name="deletionCode" wire:model="deletionCode" :label="__('client.profile.code')" autocomplete="one-time-code" inputmode="numeric" required />
                        @else
                            <x-ui.button tone="outline" size="sm" class="self-start" wire:click="sendDeletionCode">{{ __('client.profile.send_code') }}</x-ui.button>
                        @endif
                    @endif
                    <div class="flex gap-2">
                        @if ($hasPassword || $deletionCodeSent)
                            <x-ui.button type="submit" tone="coral" size="sm">{{ __('client.deletion.confirm') }}</x-ui.button>
                        @endif
                        <x-ui.button tone="outline" size="sm" wire:click="$set('confirmingDeletion', false)">{{ __('client.deletion.cancel') }}</x-ui.button>
                    </div>
                </form>
            @else
                <x-ui.button tone="outline" size="sm" class="self-start" wire:click="$set('confirmingDeletion', true)">{{ __('client.deletion.start') }}</x-ui.button>
            @endif
        </section>
    @endunless
</div>
