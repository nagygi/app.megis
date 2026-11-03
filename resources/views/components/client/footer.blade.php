{{--
    The foot of every customer page: "powered by mégis" and the legal documents
    in force, so what the user accepted when registering stays at hand (05 · 5.2).
    A document not uploaded yet has no link; the line stays without any. They open
    in a new tab, so a half-filled page is not lost. clear leaves room for the
    coral button fixed at the bottom.
--}}
@props(['clear' => false])

@php($documents = app(\App\Services\Legal\LegalDocuments::class)->available())

<footer @class([
    'mx-auto flex w-full max-w-3xl flex-wrap items-center justify-between gap-x-6 border-t border-grey-250 px-6 pt-2 text-sm text-grey-600 desktop:max-w-5xl',
    'pb-28' => $clear,
    'pb-4' => ! $clear,
])>
    <p class="flex min-h-11 items-center">{{ __('client.footer.powered_by') }}&nbsp;<strong class="font-semibold text-charcoal">{{ __('ui.brand') }}</strong></p>

    @if ($documents->isNotEmpty())
        <nav aria-label="{{ __('public.legal_navigation') }}">
            <ul class="flex flex-wrap gap-x-5">
                @foreach ($documents as $type)
                    <li>
                        <a href="{{ route('legal.show', $type->value) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center text-current">
                            {{ $type->label() }}<span class="sr-only"> ({{ __('ui.new_tab') }})</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</footer>
