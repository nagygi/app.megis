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
</div>
