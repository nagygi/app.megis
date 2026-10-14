<x-mail::message>
{{ __('mail.admin_locked.body', ['name' => $lockedUser->name, 'email' => $lockedUser->email]) }}

{{ __('mail.admin_locked.unlock', ['email' => $lockedUser->email]) }}
</x-mail::message>
