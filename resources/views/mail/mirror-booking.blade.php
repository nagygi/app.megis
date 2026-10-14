<x-mail::message>
{{ $summary }}

<x-mail::button :url="$url">
{{ __('mail.mirror_booking.action') }}
</x-mail::button>
</x-mail::message>
