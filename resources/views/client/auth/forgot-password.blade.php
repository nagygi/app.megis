{{-- "Elfelejtettem a jelszavam" (14 · 14.3) --}}
<x-layouts::client
    :title="__('auth_ui.customer.forgot_title')"
    :back="route('client.login')"
    :back-label="__('auth_ui.customer.back_to_login')"
    guest
>
    <form method="POST" action="{{ route('client.password.email') }}" class="flex max-w-md flex-col gap-5">
        @csrf

        <p>{{ __('auth_ui.customer.forgot_lead') }}</p>

        <x-form.input name="email" type="email" :label="__('auth_ui.email')" :value="old('email', $email)" autocomplete="email" required autofocus />

        <x-turnstile />

        <x-ui.button type="submit" size="lg" icon-right="arrow-right" class="self-start">{{ __('auth_ui.customer.forgot_button') }}</x-ui.button>
    </form>
</x-layouts::client>
