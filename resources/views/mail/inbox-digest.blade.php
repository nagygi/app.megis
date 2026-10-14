<x-mail::message>
{{ __('mail.inbox_digest.intro', ['count' => count($lines)]) }}

@foreach ($lines as $line)
- {{ $line }}
@endforeach

<x-mail::button :url="$url">
{{ __('mail.inbox_digest.action') }}
</x-mail::button>
</x-mail::message>
