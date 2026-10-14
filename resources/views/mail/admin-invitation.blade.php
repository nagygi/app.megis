<x-mail::message>
{{ __('mail.admin_invitation.body', ['inviter' => $inviter?->name ?? __('ui.brand')]) }}

<x-mail::button :url="$url">
{{ __('mail.admin_invitation.action') }}
</x-mail::button>

{{ __('mail.admin_invitation.validity') }}
</x-mail::message>
