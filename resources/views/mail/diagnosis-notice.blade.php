<x-mail::message>
{{ $body }}

<x-mail::button :url="$url">
{{ $action }}
</x-mail::button>
</x-mail::message>
