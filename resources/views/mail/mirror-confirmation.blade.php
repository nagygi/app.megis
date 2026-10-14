<x-mail::message>
{{ __('mail.mirror_confirmation.body') }}

<x-mail::button :url="$url">
{{ __('mail.mirror_confirmation.action') }}
</x-mail::button>

{{ __('mail.mirror_confirmation.validity') }}
</x-mail::message>
