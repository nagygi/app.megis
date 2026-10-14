import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Livewire is bundled here instead of being auto-injected, so Alpine is present
// on every page and app-wide Alpine components can be registered before start.
window.Alpine = Alpine;

Livewire.start();

// Installable app with an offline notice page (15 · 15.7).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}
