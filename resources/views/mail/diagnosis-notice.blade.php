<x-mail::message>
{{ $body }}

<x-mail::button :url="$url">
{{ $action }}
</x-mail::button>
@if ($secondaryAction && $secondaryUrl)

{{ __('mail.diagnosis_published.booking_lead') }}

<x-mail::button :url="$secondaryUrl" color="success">
{{ $secondaryAction }}
</x-mail::button>
@endif
</x-mail::message>
