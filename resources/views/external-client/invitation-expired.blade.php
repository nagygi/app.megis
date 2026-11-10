{{-- An expired or used personal link (35 · 35.5): an activated account asks for a login code on B1. --}}
<x-layouts::client :title="__('external_clients.invitation.expired_title')" guest>
    <div class="flex max-w-md flex-col gap-5">
        <p>{{ __('external_clients.invitation.expired') }}</p>
        <x-ui.button :href="route('client.login')" size="lg" class="self-start">{{ __('external_clients.invitation.to_login') }}</x-ui.button>
    </div>
</x-layouts::client>
