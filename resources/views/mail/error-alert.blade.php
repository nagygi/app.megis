<x-mail::message>
{{ __('mail.error_alert.intro', ['environment' => $environment]) }}

<x-mail::table>
| | |
|:--|:--|
| {{ __('mail.error_alert.environment') }} | {{ $environment }} |
| {{ __('mail.error_alert.error') }} | {{ $errorClass }} |
| {{ __('mail.error_alert.message') }} | {{ $errorMessage }} |
@if ($url)
| {{ __('mail.error_alert.url') }} | {{ $url }} |
@endif
@if ($jobName)
| {{ __('mail.error_alert.job') }} | {{ $jobName }} |
@endif
| {{ __('mail.error_alert.time') }} | {{ $occurredAt->format('Y.m.d. H:i:s') }} |
| {{ __('mail.error_alert.log_id') }} | {{ $logId ?? '–' }} |
</x-mail::table>

{{ __('mail.error_alert.throttle_note', ['minutes' => $throttleMinutes]) }}
</x-mail::message>
