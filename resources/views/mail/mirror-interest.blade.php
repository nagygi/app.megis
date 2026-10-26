<x-mail::message>
{{ __('mail.mirror_interest.intro', ['name' => $name]) }}

@foreach ($questions as $question)
- {{ $question }}
@endforeach

@if ($values !== [])
{{ __('mail.mirror_interest.values', ['values' => implode(' · ', $values)]) }}
@endif

@if ($email)
{{ __('mail.mirror_interest.reply', ['email' => $email]) }}
@endif

@if ($url)
<x-mail::button :url="$url">
{{ __('mail.mirror_interest.action') }}
</x-mail::button>
@endif
</x-mail::message>
