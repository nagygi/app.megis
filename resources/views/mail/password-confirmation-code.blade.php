<x-mail::message>
{{ __('mail.password_confirmation_code.body') }}

<x-mail::panel>
**{{ $code }}**
</x-mail::panel>

{{ __('mail.password_confirmation_code.validity') }}
</x-mail::message>
