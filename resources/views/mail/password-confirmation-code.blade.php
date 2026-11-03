<x-mail::message>
{{ $body }}

<x-mail::panel>
**{{ $code }}**
</x-mail::panel>

{{ __('mail.password_confirmation_code.validity') }}
</x-mail::message>
