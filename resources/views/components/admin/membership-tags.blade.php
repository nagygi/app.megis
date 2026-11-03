{{-- A membership's tags as small chips (02 · 2.5); the organization leader stands out. --}}
@props(['membership'])

<span {{ $attributes->class('inline-flex flex-wrap gap-1') }}>
    @foreach ($membership->tagLabels() as $tag)
        <x-ui.chip size="sm" :strong="$tag === __('enums.membership_tag.organization_leader')">{{ $tag }}</x-ui.chip>
    @endforeach
</span>
