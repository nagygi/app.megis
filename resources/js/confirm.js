/**
 * The brand confirmation instead of the browser's confirm(): asks in the
 * page's <dialog id="megis-confirm"> (x-ui.confirm-dialog) and resolves to
 * true on "Igen". Esc, "Mégse" and a click on the backdrop answer no.
 * Without the dialog (a page whose layout lacks it) the browser asks.
 *
 * @param {{ message: string, tone?: string, label?: string }} options
 * @returns {Promise<boolean>}
 */
export default function megisConfirm({ message, tone, label }) {
    const dialog = document.getElementById('megis-confirm');

    if (!dialog || typeof dialog.showModal !== 'function' || dialog.open) {
        return Promise.resolve(window.confirm(message));
    }

    const danger = tone === 'danger';
    dialog.querySelector('[data-confirm-message]').textContent = message;

    dialog.querySelectorAll('[data-confirm-ok]').forEach((button) => {
        const shown = (button.dataset.confirmOk === 'danger') === danger;
        button.hidden = !shown;

        if (shown) {
            button.querySelector('[data-confirm-label]').textContent = label || button.dataset.defaultLabel;
        }
    });

    dialog.returnValue = '';
    dialog.showModal();

    return new Promise((resolve) => {
        const onBackdrop = (event) => {
            if (event.target === dialog) {
                dialog.close('');
            }
        };

        dialog.addEventListener('click', onBackdrop);
        dialog.addEventListener('close', () => {
            dialog.removeEventListener('click', onBackdrop);
            resolve(dialog.returnValue === 'ok');
        }, { once: true });
    });
}
