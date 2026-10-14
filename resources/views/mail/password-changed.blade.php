<x-mail::message>
{{ __('mail.password_changed.body', ['time' => $time]) }}

{{ $note }}
</x-mail::message>
