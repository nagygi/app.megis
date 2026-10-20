{{--
    The page's toasts (resources/js/toasts.js): what x-ui.flash and
    $this->dispatch('toast') report, shown where the user is instead of at the
    top of a long page. Two live regions, there from the start, so screen
    readers announce what arrives: polite for success, assertive for errors.
    Bottom centre on phones, bottom right from 1200px. In the layout, outside
    every Livewire component.
--}}
<div
    x-data
    @toast.window="$store.toasts.add($event.detail.type, $event.detail.message)"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] desktop:items-end desktop:px-6 desktop:pb-6"
>
    @foreach (['success' => 'polite', 'error' => 'assertive'] as $type => $politeness)
        <div aria-live="{{ $politeness }}" class="flex w-full flex-col items-center gap-2 desktop:items-end">
            <template x-for="toast in $store.toasts.items.filter((toast) => toast.type === '{{ $type }}')" :key="toast.id">
                <div
                    @mouseenter="$store.toasts.pause(toast)"
                    @mouseleave="$store.toasts.resume(toast)"
                    @class([
                        'on-dark pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-card py-2 pr-2 pl-4 text-cream',
                        // Arriving, it moves up a little; it never fades, so its text is readable from the first frame.
                        'transition-[translate] duration-[420ms] ease-brand-out starting:translate-y-2',
                        'bg-green' => $type === 'success',
                        'bg-charcoal' => $type === 'error',
                    ])
                >
                    @if ($type === 'success')
                        <x-megis.icon name="check" class="mt-3" />
                    @endif
                    <p class="flex-1 py-2" x-text="toast.message"></p>
                    <button
                        type="button"
                        @click="$store.toasts.remove(toast.id)"
                        @class([
                            'press grid size-10 shrink-0 place-items-center rounded-pill',
                            'hover:bg-green-muted' => $type === 'success',
                            'hover:bg-grey-700' => $type === 'error',
                        ])
                    >
                        <x-megis.icon name="close" :label="__('ui.close')" />
                    </button>
                </div>
            </template>
        </div>
    @endforeach
</div>
