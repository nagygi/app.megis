{{-- B2 with "Ez a link már lejárt" (14 · 14.2, Extra inputok 2. állapot) --}}
<x-layouts::client :title="__('auth_ui.customer.link_expired_title')" guest>
    <div class="flex max-w-md flex-col gap-5">
        <p>{{ __('auth_ui.customer.link_expired') }}</p>

        @if ($canResend)
            <form method="POST" action="{{ route('client.login.link.resend', $token) }}" class="flex flex-col gap-5">
                @csrf
                <x-turnstile />
                <x-ui.button type="submit" size="lg" class="self-start">{{ __('auth_ui.customer.request_new') }}</x-ui.button>
            </form>
        @else
            <x-ui.button :href="route('client.login')" size="lg" class="self-start">{{ __('auth_ui.customer.request_new') }}</x-ui.button>
        @endif
    </div>
</x-layouts::client>
