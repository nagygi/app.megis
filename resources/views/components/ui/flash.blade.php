{{--
    Success and error feedback after an action, from the session.

    By default it hands the message to the page's toasts (x-ui.toasts), so it
    shows where the user is, not at the top of a long page; the text stays in
    the HTML. Each render gets a new wire:key, so Livewire replaces the element
    and the same message ("Mentve") shows again after the next save.
    "inline" keeps it in the page, read out by screen readers: the login pages
    show it next to their form.
--}}
@props(['inline' => false])

@if ($inline)
    @if (session('status'))
        <div role="status" {{ $attributes->class(['flex items-start gap-2 rounded-card bg-green-soft px-4 py-3 text-green']) }}>
            <x-megis.icon name="check" class="mt-0.5" />
            <p>{{ session('status') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div role="alert" {{ $attributes->class(['flex items-start gap-2 rounded-card bg-yellow-pale px-4 py-3 text-charcoal']) }}>
            <x-megis.icon name="close" class="mt-0.5" />
            <p>{{ session('error') }}</p>
        </div>
    @endif
@else
    @foreach (['status' => 'success', 'error' => 'error'] as $key => $type)
        @if (session($key))
            <p hidden wire:key="flash-{{ $key }}-{{ \Illuminate\Support\Str::random(8) }}" x-data x-init="$store.toasts.add('{{ $type }}', $el.textContent)">{{ session($key) }}</p>
        @endif
    @endforeach
@endif
