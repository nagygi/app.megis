<x-mail::message>
{{ $intro }}

<x-mail::button :url="$url">
{{ __('mail.external_client.action') }}
</x-mail::button>

{{ __('mail.external_client.validity') }}
</x-mail::message>
