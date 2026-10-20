{{-- B1 · Belépés (14 · 14.2) --}}
<x-layouts::client :title="__('auth_ui.customer.login_title')" guest>
    <x-ui.flash inline class="mb-6 max-w-md" />

    <form
        method="POST"
        action="{{ route('client.login.store') }}"
        x-data="{ withPassword: {{ $errors->has('password') ? 'true' : 'false' }} }"
        class="flex max-w-md flex-col gap-5"
    >
        @csrf

        <x-form.input name="email" type="email" :label="__('auth_ui.email')" :value="old('email', $email)" autocomplete="email" required autofocus />

        <template x-if="withPassword">
            <x-form.input name="password" type="password" :label="__('auth_ui.password')" autocomplete="current-password" />
        </template>

        <x-turnstile />

        <x-ui.button type="submit" size="lg" class="self-start">{{ __('auth_ui.customer.login_button') }}</x-ui.button>

        <button
            type="button"
            class="self-start text-sm font-semibold underline"
            @click="withPassword = ! withPassword"
            x-text="withPassword ? @js(__('auth_ui.customer.with_code')) : @js(__('auth_ui.customer.with_password'))"
        >{{ __('auth_ui.customer.with_password') }}</button>

        <p class="text-sm text-grey-600">
            {{ __('auth_ui.customer.no_account') }}
            <a href="{{ route('public.home') }}" class="font-semibold text-charcoal">{{ __('auth_ui.customer.no_account_link') }}</a>
        </p>
    </form>
</x-layouts::client>
