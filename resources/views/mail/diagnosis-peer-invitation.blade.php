<x-mail::message>
{{ $intro }}

<x-mail::button :url="$url">
{{ __('mail.diagnosis_peer.action') }}
</x-mail::button>

@if ($isNew)
{{ __('mail.client_invitation.validity') }}
@endif
</x-mail::message>
