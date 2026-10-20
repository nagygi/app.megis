import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import mirrorRecorder from './recorder';
import dictation from './dictation';
import markdownEditor from './markdown-editor';
import turnstileWidget from './turnstile';
import megisConfirm from './confirm';
import toasts from './toasts';

// Livewire is bundled here instead of being auto-injected, so Alpine is present
// on every page and app-wide Alpine components can be registered before start.
window.Alpine = Alpine;

Alpine.data('mirrorRecorder', mirrorRecorder);
Alpine.data('dictation', dictation);
Alpine.data('markdownEditor', markdownEditor);
Alpine.data('turnstileWidget', turnstileWidget);
Alpine.store('toasts', toasts);

// wire:confirm asks in the brand dialog instead of the browser's confirm().
// Livewire's own handler runs first and sets __livewire_confirm, which its
// click and submit handlers call with the action; this one replaces it.
// data-confirm-tone="danger" paints the yes button as destructive and
// data-confirm-label names it (e.g. "Törlöm").
Livewire.hook('directive.init', ({ el, directive }) => {
    if (directive.value !== 'confirm' || directive.modifiers.includes('prompt')) {
        return;
    }

    const message = directive.expression.replaceAll('\\n', '\n');

    el.__livewire_confirm = (action, instead) => {
        megisConfirm({ message, tone: el.dataset.confirmTone, label: el.dataset.confirmLabel })
            .then((confirmed) => (confirmed && el.isConnected ? action() : instead()));
    };
});

// A step's "Tovább" (data-focus-errors) that fails validation hands the focus
// to the first invalid field or error line, which may be far up the page.
let focusErrorsAfterUpdate = false;

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-focus-errors]')) {
        focusErrorsAfterUpdate = true;
    }
}, true);

Livewire.hook('commit', ({ succeed }) => {
    succeed(() => requestAnimationFrame(() => requestAnimationFrame(() => {
        if (!focusErrorsAfterUpdate) {
            return;
        }

        focusErrorsAfterUpdate = false;
        const error = document.querySelector('[aria-invalid="true"], main p[id$="-error"]');

        if (error) {
            error.matches('input, select, textarea, button') || error.setAttribute('tabindex', '-1');
            error.focus({ preventScroll: true });
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            error.scrollIntoView({ block: 'center', behavior: reduced ? 'auto' : 'smooth' });
        }
    })));
});

Livewire.start();

// Plain forms (login, logout, legal acceptance) show the same busy button as
// Livewire's data-loading while the next page loads, and are not sent twice.
// Livewire's own wire:submit forms arrive here already prevented.
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (event.defaultPrevented || form.target === '_blank' || form.method === 'dialog') {
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
