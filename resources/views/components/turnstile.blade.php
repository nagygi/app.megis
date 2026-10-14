{{--
    Cloudflare Turnstile widget (NFR · 5). Cloudflare adds the hidden
    "cf-turnstile-response" field to the surrounding form.
--}}
<div {{ $attributes->class(['flex flex-col gap-1.5']) }}>
    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="hu" data-theme="light"></div>
    <x-form.error name="cf-turnstile-response" id="cf-turnstile-response" />
</div>

@pushOnce('head')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endPushOnce
