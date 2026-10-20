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

// Plain forms (login, logout, legal acceptance) show the same busy button as
// Livewire's data-loading while the next page loads, and are not sent twice.
// Livewire's own wire:submit forms arrive here already prevented.
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (event.defaultPrevented || form.target === '_blank') {
        return;
    }

    if (form.dataset.submitting !== undefined) {
        event.preventDefault();

        return;
    }

    form.dataset.submitting = '';
    event.submitter?.setAttribute('data-loading', '');

    // A download keeps the page, so the form frees itself after a while.
    setTimeout(() => releaseForm(form), 8000);
});

// Back from the browser's page cache, the form must be usable again.
window.addEventListener('pageshow', () => document.querySelectorAll('form[data-submitting]').forEach(releaseForm));

function releaseForm(form) {
    delete form.dataset.submitting;
    form.querySelectorAll('[data-loading]').forEach((element) => element.removeAttribute('data-loading'));
}

// Installable app with an offline notice page (15 · 15.7).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}
