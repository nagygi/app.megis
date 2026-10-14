<x-mail::message>
{{ $intro }}

{{ $timing }}

<x-mail::button :url="$url">
{{ __('mail.diagnosis_leader.action') }}
</x-mail::button>

@if ($token)
{{ __('mail.client_invitation.validity') }}
@endif
</x-mail::message>
