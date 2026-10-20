/**
 * The page's toasts (x-ui.toasts): x-ui.flash hands its session message over,
 * and a Livewire component can raise one with $this->dispatch('toast',
 * message: '…', type: 'error'). Success fades after a while unless the
 * pointer rests on it; an error stays until it is closed.
 */
const SUCCESS_MS = 6000;

export default {
    items: [],
    nextId: 1,

    add(type, message) {
        const text = String(message ?? '').trim();

        if (text === '') {
            return;
        }

        const kind = type === 'error' ? 'error' : 'success';
        // The same message again replaces the old one instead of stacking.
        this.items = this.items.filter((toast) => !(toast.type === kind && toast.message === text));

        const toast = { id: this.nextId++, type: kind, message: text, timer: null };
        this.items.push(toast);
        this.resume(toast);
    },

    pause(toast) {
        clearTimeout(toast.timer);
    },

    resume(toast) {
        if (toast.type === 'success') {
            clearTimeout(toast.timer);
            toast.timer = setTimeout(() => this.remove(toast.id), SUCCESS_MS);
        }
    },

    remove(id) {
        this.items = this.items.filter((toast) => toast.id !== id);
    },
};
