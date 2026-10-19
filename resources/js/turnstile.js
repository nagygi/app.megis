/**
 * The Turnstile widget of a Livewire form (NFR · 5). The widget may appear
 * only after a Livewire update (the Értéktükör's "A teljes kép"), when the
 * script pushed into the head was never on the page, so it loads the script
 * itself. After every check the server asks for a new token with
 * "turnstile-reset", as Cloudflare accepts a token only once.
 */
const SCRIPT = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

const loadScript = () => new Promise((resolve) => {
    if (! document.querySelector(`script[src^="${SCRIPT}"]`)) {
        const script = document.createElement('script');
        script.src = `${SCRIPT}?render=explicit`;
        script.async = true;
        document.head.append(script);
    }

    const wait = () => (window.turnstile ? resolve(window.turnstile) : setTimeout(wait, 100));
    wait();
});

export default ({ sitekey, model }) => ({
    widgetId: null,

    async init() {
        const turnstile = await loadScript();

        this.widgetId = turnstile.render(this.$refs.widget, {
            sitekey,
            language: 'hu',
            theme: 'light',
            callback: (token) => this.$wire.set(model, token, false),
            'expired-callback': () => this.$wire.set(model, '', false),
        });
    },

    reset() {
        if (this.widgetId !== null) {
            window.turnstile.reset(this.widgetId);
        }
    },

    destroy() {
        if (this.widgetId !== null) {
            window.turnstile.remove(this.widgetId);
        }
    },
});
