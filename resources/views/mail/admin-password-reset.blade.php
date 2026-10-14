<x-mail::message>
{{ __('mail.admin_password_reset.body', ['admin' => $admin->name]) }}

<x-mail::button :url="$url">
{{ __('mail.admin_password_reset.action') }}
</x-mail::button>

{{ __('mail.admin_password_reset.validity') }}
</x-mail::message>
