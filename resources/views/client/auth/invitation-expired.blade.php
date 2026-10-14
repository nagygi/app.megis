{{-- An expired or invalid invitation (14 · 14.1). Only the inviter can send a new one. --}}
<x-layouts::client :title="__('auth_ui.registration.expired_title')" guest>
    <p class="max-w-md">{{ __('auth_ui.registration.expired') }}</p>
</x-layouts::client>
