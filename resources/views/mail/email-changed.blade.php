<x-mail::message>
@if ($toOldAddress)
{{ __('mail.email_changed.old_address', ['email' => $newEmail, 'support' => $support]) }}
@else
{{ __('mail.email_changed.new_address') }}
@endif
</x-mail::message>
