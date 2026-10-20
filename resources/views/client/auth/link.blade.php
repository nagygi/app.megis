{{-- Login link page: only the button uses the link up (14 · 14.1). --}}
<x-layouts::client :title="__('auth_ui.customer.link_title')" guest>
    <form method="POST" action="{{ route('client.login.link.store', $token) }}" class="flex max-w-md flex-col gap-5">
        @csrf
        <p>{{ __('auth_ui.customer.link_lead') }}</p>
        <x-ui.button type="submit" size="lg" icon-right="arrow-right" class="self-start">{{ __('auth_ui.customer.link_button') }}</x-ui.button>
    </form>
</x-layouts::client>
