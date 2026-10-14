{{-- Registration from the invitation, B1 header (14 · 14.1) --}}
<x-layouts::client
    :title="__('auth_ui.registration.title')"
    :subtitle="$inviter ? __('auth_ui.registration.context', ['inviter' => $inviter->name, 'organization' => $organization->name]) : null"
    guest
>
    <form
        method="POST"
        action="{{ route('client.invitation.store', $token) }}"
        x-data="{ accepted: {{ old('accept') ? 'true' : 'false' }} }"
        class="flex max-w-md flex-col gap-5"
    >
        @csrf

        <p class="font-semibold">{{ $user->email }}</p>

        <x-form.input name="last_name" :label="__('auth_ui.registration.last_name')" :value="old('last_name', $user->last_name)" autocomplete="family-name" required />
        <x-form.input name="first_name" :label="__('auth_ui.registration.first_name')" :value="old('first_name', $user->first_name)" autocomplete="given-name" required />

        <x-form.input
            name="password"
            type="password"
            :label="__('auth_ui.registration.password_optional')"
            :help="__('auth_ui.registration.password_optional_help').' '.\App\Support\PasswordRules::hintFor(null)"
            autocomplete="new-password"
        />
        <x-form.input name="password_confirmation" type="password" :label="__('auth_ui.password_confirmation')" autocomplete="new-password" />

        <x-form.checkbox name="accept" x-model="accepted" :checked="(bool) old('accept')">
            @if ($terms && $privacy)
                {!! __('auth_ui.registration.accept', [
                    'terms' => '<a href="'.e(route('legal.show', 'aszf')).'" target="_blank" class="font-semibold text-charcoal">'.e(__('auth_ui.registration.accept_terms')).'</a>',
                    'privacy' => '<a href="'.e(route('legal.show', 'adatkezelesi-tajekoztato')).'" target="_blank" class="font-semibold text-charcoal">'.e(__('auth_ui.registration.accept_privacy')).'</a>',
                ]) !!}
            @else
                {{ __('auth_ui.registration.accept_without_documents') }}
            @endif
        </x-form.checkbox>

        <x-turnstile />

        <x-ui.button type="submit" size="lg" class="self-start" ::disabled="! accepted">{{ __('auth_ui.registration.button') }}</x-ui.button>
    </form>
</x-layouts::client>
