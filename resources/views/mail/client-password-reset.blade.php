<x-mail::message>
{{ $body }}

<x-mail::button :url="$url">
{{ __('mail.client_password_reset.action') }}
</x-mail::button>

{{ __('mail.client_password_reset.validity') }}
</x-mail::message>
