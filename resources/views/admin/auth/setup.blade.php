<x-layouts::auth :title="__('auth_ui.admin.setup_title')">
    <form method="POST" action="{{ route('admin.password.setup.store', $token) }}" class="flex flex-col gap-5">
        @csrf

        <p>{{ __('auth_ui.admin.setup_lead') }}</p>
        <p class="font-semibold">{{ $user->email }}</p>

        <x-form.input
            name="password"
            type="password"
            :label="__('auth_ui.new_password')"
            :help="\App\Support\PasswordRules::hintFor($user)"
            autocomplete="new-password"
            required
        />
        <x-form.input name="password_confirmation" type="password" :label="__('auth_ui.password_confirmation')" autocomplete="new-password" required />

        <x-turnstile />

        <x-ui.button type="submit" class="self-start">{{ __('auth_ui.admin.setup_button') }}</x-ui.button>
    </form>
</x-layouts::auth>
