{{--
    The page's main button under an x-recorder without its inline submit (06 · 6.1):
    it follows the recorder's state and sends the finished recording. Until the
    recording is finished ("Befejeztem") it is disabled and says why, so a
    running recording is never cut by the send.
--}}
<div {{ $attributes->class(['flex flex-col gap-2']) }} x-data="{ recorder: 'idle' }" x-on:recorder-state.window="recorder = $event.detail">
    <x-ui.button
        size="lg"
        icon-right="arrow-right"
        class="self-start"
        data-focus-errors
        x-bind:disabled="recorder !== 'recorded' && recorder !== 'uploading'"
        x-bind:data-loading="recorder === 'uploading'"
        x-on:click="$dispatch('recorder-submit')"
    >{{ $slot }}</x-ui.button>
    <p x-show="recorder === 'idle'" class="text-sm text-grey-700">{{ __('mirror.recording_missing') }}</p>
    <p x-show="recorder === 'recording' || recorder === 'paused'" x-cloak class="text-sm text-grey-700">{{ __('mirror.recording_unfinished') }}</p>
</div>
