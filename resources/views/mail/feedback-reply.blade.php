<x-mail::message>
{{ $greeting }}

{{ __('mail.feedback_reply.lead') }}

> {{ $quote }}

{{ $reply }}

<x-mail::button :url="$url">
{{ __('mail.feedback_reply.action') }}
</x-mail::button>
</x-mail::message>
