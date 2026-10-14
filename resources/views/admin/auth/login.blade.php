<x-layouts::auth :title="__('auth_ui.admin.login_title')">
    <form method="POST" action="{{ route('admin.login.store') }}" class="flex flex-col gap-5">
        @csrf

        <x-form.input name="email" type="email" :label="__('auth_ui.email')" :value="old('email')" autocomplete="username" required autofocus />
        <x-form.input name="password" type="password" :label="__('auth_ui.password')" autocomplete="current-password" required />

        <x-turnstile />

        <x-ui.button type="submit" class="self-start">{{ __('auth_ui.admin.login_button') }}</x-ui.button>

        <p class="text-sm text-grey-600">{{ __('auth_ui.admin.forgot_password') }}</p>
    </form>
</x-layouts::auth>
