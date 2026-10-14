<x-mail::message>
{{ __('mail.organization_added.body', ['organization' => $organization->name]) }}

<x-mail::button :url="$url">
{{ __('mail.organization_added.action') }}
</x-mail::button>
</x-mail::message>
