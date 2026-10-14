{{-- Success and error feedback after a form submit, read out by screen readers. --}}
@if (session('status'))
    <div role="status" {{ $attributes->class(['flex items-start gap-2 rounded-card bg-green-soft px-4 py-3 text-green']) }}>
        <x-megis.icon name="check" class="mt-0.5" />
        <p>{{ session('status') }}</p>
    </div>
@endif

@if (session('error'))
    <div role="alert" {{ $attributes->class(['flex items-start gap-2 rounded-card bg-yellow-pale px-4 py-3 text-charcoal']) }}>
        <x-megis.icon name="close" class="mt-0.5 text-coral-dark" />
        <p>{{ session('error') }}</p>
    </div>
@endif
