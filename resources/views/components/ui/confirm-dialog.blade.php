{{--
    The brand confirmation behind wire:confirm (resources/js/confirm.js). One per
    page, in the layout and outside every Livewire component, so a re-render
    never closes it. "Mégse" has the focus first, so Enter does not confirm by
    accident; Esc and a click beside the card answer no as well. The yes button
    is cloned from one of the two templates as the dialog opens, so the page
    holds a single "Igen" in the tone the action asks for.
--}}
<dialog
    id="megis-confirm"
    aria-labelledby="megis-confirm-message"
    class="m-auto w-[calc(100%-2rem)] max-w-md rounded-card bg-white p-6 text-charcoal transition-[translate] duration-200 ease-standard backdrop:bg-charcoal/40 starting:open:translate-y-2"
>
    <form method="dialog" class="flex flex-col gap-6">
        <p id="megis-confirm-message" data-confirm-message class="text-lg font-semibold whitespace-pre-line [text-wrap:pretty]"></p>

        <div class="flex flex-wrap justify-end gap-2">
            <x-ui.button type="submit" value="cancel" tone="outline" autofocus>{{ __('ui.cancel') }}</x-ui.button>
            <span data-confirm-ok-slot class="contents"></span>
        </div>
    </form>

    <template data-confirm-template="default">
        <x-ui.button type="submit" value="ok" :data-default-label="__('ui.confirm')"><span data-confirm-label>{{ __('ui.confirm') }}</span></x-ui.button>
    </template>
    <template data-confirm-template="danger">
        <x-ui.button type="submit" value="ok" tone="danger" :data-default-label="__('ui.confirm')"><span data-confirm-label>{{ __('ui.confirm') }}</span></x-ui.button>
    </template>
</dialog>
