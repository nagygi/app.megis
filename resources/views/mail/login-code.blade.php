<x-mail::message>
{{ __('mail.login_code.body') }}

<x-mail::panel>
**{{ $code }}**
</x-mail::panel>

<x-mail::button :url="$url">
{{ __('mail.login_code.action') }}
</x-mail::button>

{{ __('mail.login_code.validity') }}
</x-mail::message>
