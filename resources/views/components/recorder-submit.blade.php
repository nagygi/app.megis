{{--
    The page's main button under an x-recorder without its inline submit (06 · 6.1):
    it follows the recorder's state, stops a running recording and sends it.
    Until there is a recording it is disabled and says why.
--}}
<div {{ $attributes->class(['flex flex-col gap-2']) }} x-data="{ recorder: 'idle' }" x-on:recorder-state.window="recorder = $event.detail">
    <x-ui.button
        size="lg"
        icon-right="arrow-right"
        class="self-start"
        data-focus-errors
        x-bind:disabled="recorder === 'idle'"
        x-bind:data-loading="recorder === 'uploading'"
        x-on:click="$dispatch('recorder-submit')"
    >{{ $slot }}</x-ui.button>
    <p x-show="recorder === 'idle'" class="text-sm text-grey-700">{{ __('mirror.recording_missing') }}</p>
</div>
