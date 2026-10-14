{{-- B2 · Nézd meg a leveled (14 · 14.2) --}}
<x-layouts::client
    :title="__('auth_ui.customer.code_title')"
    :back="route('client.login')"
    :back-label="__('auth_ui.customer.other_email')"
    guest
>
    <div class="flex max-w-md flex-col gap-6">
        <p>{{ __('auth_ui.customer.code_lead', ['email' => $email]) }}</p>

        <form method="POST" action="{{ route('client.login.code.store') }}" class="flex flex-col gap-5">
            @csrf

            <x-form.input
                name="code"
                :label="__('auth_ui.customer.code')"
                :help="__('auth_ui.customer.code_help')"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                class="text-2xl tracking-[0.3em]"
                required
                autofocus
            />

            <x-ui.button type="submit" size="lg" class="self-start">{{ __('auth_ui.customer.code_button') }}</x-ui.button>
        </form>

        <div class="flex flex-wrap items-center gap-3" x-data="{ seconds: {{ $secondsUntilResend }} }" x-init="setInterval(() => { if (seconds > 0) seconds-- }, 1000)">
            <form method="POST" action="{{ route('client.login.code.resend') }}">
                @csrf
                <x-ui.button type="submit" tone="outline" size="sm" ::disabled="seconds > 0">{{ __('auth_ui.customer.resend') }}</x-ui.button>
            </form>
            <p class="text-sm text-grey-600" x-show="seconds > 0" x-cloak aria-live="polite">
                <span x-text="@js(__('auth_ui.customer.resend_wait', ['seconds' => '#'])).replace('#', seconds)"></span>
            </p>
            <a href="{{ route('client.login') }}" class="text-sm font-semibold text-charcoal">{{ __('auth_ui.customer.other_email') }}</a>
        </div>
    </div>
</x-layouts::client>
