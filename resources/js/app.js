import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import mirrorRecorder from './recorder';
import dictation from './dictation';
import markdownEditor from './markdown-editor';
import turnstileWidget from './turnstile';

// Livewire is bundled here instead of being auto-injected, so Alpine is present
// on every page and app-wide Alpine components can be registered before start.
window.Alpine = Alpine;

Alpine.data('mirrorRecorder', mirrorRecorder);
Alpine.data('dictation', dictation);
Alpine.data('markdownEditor', markdownEditor);
Alpine.data('turnstileWidget', turnstileWidget);

Livewire.start();

// Installable app with an offline notice page (15 · 15.7).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}
