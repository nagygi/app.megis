<x-mail::message>
{{ __('mail.client_invitation.body', ['inviter' => $inviter->name, 'organization' => $organization->name]) }}

<x-mail::button :url="$url">
{{ __('mail.client_invitation.action') }}
</x-mail::button>

{{ __('mail.client_invitation.validity') }}
</x-mail::message>
