{{-- The footer of every public page: links to the legal documents in force; a document not uploaded yet has no link, and with none there is no footer (05 · 5.2). --}}
@props(['tone' => 'cream'])

@php
    $legalDocuments = app(\App\Services\Legal\LegalDocuments::class);
    $documents = collect(\App\Enums\LegalDocumentType::cases())->filter(fn ($type) => $legalDocuments->current($type) !== null);
@endphp

@if ($documents->isNotEmpty())
    <footer @class([
        'mx-auto max-w-5xl border-t px-4 py-6 text-sm sm:px-8',
        'border-grey-250 text-grey-600' => $tone === 'cream',
        'border-black/20 text-black' => $tone === 'coral',
    ])>
        <nav aria-label="{{ __('public.legal_navigation') }}">
            <ul class="flex flex-wrap gap-x-6 gap-y-2">
                @foreach ($documents as $type)
                    <li><a href="{{ route('legal.show', $type->value) }}" class="text-current">{{ $type->label() }}</a></li>
                @endforeach
            </ul>
        </nav>
    </footer>
@endif
