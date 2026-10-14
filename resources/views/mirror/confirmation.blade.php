{{-- The short form after the Értéktükör confirmation link (06 · 6.1). --}}
<x-layouts::client :title="__('mirror.confirm_title')" guest>
    <form method="POST" action="{{ route('mirror.confirmation.store', $token) }}" class="flex max-w-md flex-col gap-5">
        @csrf

        <p>{{ __('mirror.confirm_lead') }}</p>
        <p class="font-semibold">{{ $email }}</p>

        <x-form.input name="last_name" :label="__('auth_ui.registration.last_name')" :value="old('last_name')" autocomplete="family-name" required />
        <x-form.input name="first_name" :label="__('auth_ui.registration.first_name')" :value="old('first_name')" autocomplete="given-name" required />

        <x-form.input
            name="password"
            type="password"
            :label="__('auth_ui.registration.password_optional')"
            :help="__('auth_ui.registration.password_optional_help').' '.\App\Support\PasswordRules::hintFor(null)"
            autocomplete="new-password"
        />
        <x-form.input name="password_confirmation" type="password" :label="__('auth_ui.password_confirmation')" autocomplete="new-password" />

        <x-ui.button type="submit" size="lg" class="self-start">{{ __('mirror.confirm_button') }}</x-ui.button>
    </form>
</x-layouts::client>
