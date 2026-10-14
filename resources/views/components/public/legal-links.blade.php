{{-- Footer links to the legal documents in force; a document not uploaded yet has no link (05 · 5.2). --}}
@php
    $legalDocuments = app(\App\Services\Legal\LegalDocuments::class);
    $documents = collect(\App\Enums\LegalDocumentType::cases())->filter(fn ($type) => $legalDocuments->current($type) !== null);
@endphp

<nav aria-label="{{ __('public.legal_navigation') }}">
    <ul class="flex flex-wrap gap-x-6 gap-y-2">
        @foreach ($documents as $type)
            <li><a href="{{ route('legal.show', $type->value) }}" class="text-current">{{ $type->label() }}</a></li>
        @endforeach
        @if ($supportEmail = config('megis.support_email'))
            <li><a href="mailto:{{ $supportEmail }}" class="text-current">{{ $supportEmail }}</a></li>
        @endif
    </ul>
</nav>
