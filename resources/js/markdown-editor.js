/**
 * The md editor of the admin's long texts (NFR, 20 · 20.4): a few buttons,
 * a preview and a character counter; the textarea keeps wire:model in sync.
 * EasyMDE loads only on the pages that use it, not with the app bundle.
 */
export default ({ max }) => ({
    editor: null,

    async init() {
        const input = this.$refs.input;
        const [{ default: EasyMDE }] = await Promise.all([
            import('easymde'),
            import('easymde/dist/easymde.min.css'),
        ]);

        // The component may be gone by the time the editor arrives.
        if (!input.isConnected) {
            return;
        }

        this.editor = new EasyMDE({
            element: input,
            forceSync: true,
            spellChecker: false,
            autoDownloadFontAwesome: false,
            minHeight: '5rem',
            toolbar: ['bold', 'italic', 'unordered-list', 'link', '|', 'preview'],
            status: [{
                className: 'characters',
                defaultValue: (element) => (element.textContent = `${input.value.length} / ${max}`),
                onUpdate: (element) => (element.textContent = `${this.editor?.value().length ?? 0} / ${max}`),
            }],
        });

        // CodeMirror types into its own hidden field: it takes the textarea's
        // label and help, and a click on the label focuses it (WCAG 1.3.1, 4.1.2).
        const field = this.editor.codemirror.getInputField();
        const label = input.labels?.[0];

        if (label) {
            label.id ||= `${input.id}-label`;
            field.setAttribute('aria-labelledby', label.id);
            label.addEventListener('click', () => this.editor?.codemirror.focus());
        }

        if (input.hasAttribute('aria-describedby')) {
            field.setAttribute('aria-describedby', input.getAttribute('aria-describedby'));
        }

        this.editor.codemirror.on('change', () => {
            input.value = this.editor.value();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    },

    destroy() {
        this.editor?.toTextArea();
        this.editor = null;
    },
});
