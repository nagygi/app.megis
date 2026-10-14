{{-- No active organization left (14 · 14.2). --}}
<x-layouts::client :title="__('auth_ui.customer.paused_title')" color="white">
    <div class="flex max-w-md flex-col gap-5">
        <p>{{ __('auth_ui.customer.paused') }}</p>
        <form method="POST" action="{{ route('client.logout') }}">
            @csrf
            <x-ui.button type="submit" tone="outline">{{ __('auth_ui.customer.logout') }}</x-ui.button>
        </form>
    </div>
</x-layouts::client>
