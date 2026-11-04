{{-- The page of the password link (14 · 14.3): only saving uses the link up. --}}
<x-layouts::client :title="__('auth_ui.customer.reset_title')" guest>
    <form method="POST" action="{{ route('client.password.update', $token) }}" class="flex max-w-md flex-col gap-5">
        @csrf

        <p>{{ __('auth_ui.customer.reset_lead') }}</p>
        <p class="font-semibold">{{ $user->email }}</p>

        <x-form.input
            name="password"
            type="password"
            :label="__('auth_ui.new_password')"
            :help="\App\Support\PasswordRules::hint()"
            autocomplete="new-password"
            required
            autofocus
        />
        <x-form.input name="password_confirmation" type="password" :label="__('auth_ui.password_confirmation')" autocomplete="new-password" required />

        <x-turnstile />

        <x-ui.button type="submit" size="lg" icon-right="arrow-right" class="self-start">{{ __('auth_ui.customer.reset_button') }}</x-ui.button>
    </form>
</x-layouts::client>
