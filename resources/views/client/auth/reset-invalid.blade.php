{{-- An expired, used or unknown password link (14 · 14.3) --}}
<x-layouts::client :title="__('auth_ui.customer.reset_invalid_title')" guest>
    <div class="flex max-w-md flex-col gap-5">
        <p>{{ __('auth_ui.customer.reset_invalid') }}</p>
        <x-ui.button :href="route('client.password.request')" size="lg" class="self-start">{{ __('auth_ui.customer.reset_request_new') }}</x-ui.button>
    </div>
</x-layouts::client>
