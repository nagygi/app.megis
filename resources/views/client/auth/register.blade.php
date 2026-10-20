{{-- Registration from the invitation, B1 header (14 · 14.1) --}}
<x-layouts::client
    :title="__('auth_ui.registration.title')"
    :subtitle="$inviter ? __('auth_ui.registration.context', ['inviter' => $inviter->name, 'organization' => $organization->name]) : null"
    guest
>
    <form
        method="POST"
        action="{{ route('client.invitation.store', $token) }}"
        x-data="{ accepted: {{ old('accept') ? 'true' : 'false' }}, password: '' }"
        class="flex max-w-md flex-col gap-5"
    >
        @csrf

        <p class="font-semibold">{{ $user->email }}</p>

        <x-form.input name="last_name" :label="__('auth_ui.registration.last_name')" :value="old('last_name', $user->last_name)" autocomplete="family-name" required />
        <x-form.input name="first_name" :label="__('auth_ui.registration.first_name')" :value="old('first_name', $user->first_name)" autocomplete="given-name" required />

        <x-form.input
            name="password"
            type="password"
            x-model="password"
            :label="__('auth_ui.registration.password_optional')"
            :help="__('auth_ui.registration.password_optional_help').' '.\App\Support\PasswordRules::hintFor(null)"
            autocomplete="new-password"
        />
        {{-- The password is optional, so its repeat shows only once there is one. --}}
        <div x-show="password !== '' || {{ $errors->has('password') ? 'true' : 'false' }}" x-cloak>
            <x-form.input name="password_confirmation" type="password" :label="__('auth_ui.password_confirmation')" autocomplete="new-password" />
        </div>

        <x-form.checkbox name="accept" x-model="accepted" :checked="(bool) old('accept')">
            @if ($terms && $privacy)
                {!! __('auth_ui.registration.accept', [
                    'terms' => '<a href="'.e(route('legal.show', 'aszf')).'" target="_blank" rel="noopener" class="font-semibold text-charcoal">'.e(__('auth_ui.registration.accept_terms')).'<span class="sr-only"> ('.e(__('ui.new_tab')).')</span></a>',
                    'privacy' => '<a href="'.e(route('legal.show', 'adatkezelesi-tajekoztato')).'" target="_blank" rel="noopener" class="font-semibold text-charcoal">'.e(__('auth_ui.registration.accept_privacy')).'<span class="sr-only"> ('.e(__('ui.new_tab')).')</span></a>',
                ]) !!}
            @else
                {{ __('auth_ui.registration.accept_without_documents') }}
            @endif
        </x-form.checkbox>

        <x-turnstile />

        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" size="lg" icon-right="arrow-right" class="self-start" ::disabled="! accepted">{{ __('auth_ui.registration.button') }}</x-ui.button>
            <p class="text-sm text-grey-700" x-show="! accepted" x-cloak>{{ __('auth_ui.registration.accept_needed') }}</p>
        </div>
    </form>
</x-layouts::client>
