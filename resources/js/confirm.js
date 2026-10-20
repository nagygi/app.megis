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

    dialog.querySelector('[data-confirm-message]').textContent = message;

    // One yes button at a time, in the tone the action asks for.
    const template = dialog.querySelector(`[data-confirm-template="${tone === 'danger' ? 'danger' : 'default'}"]`);
    const slot = dialog.querySelector('[data-confirm-ok-slot]');
    slot.replaceChildren(template.content.cloneNode(true));
    const yes = slot.querySelector('button');
    yes.querySelector('[data-confirm-label]').textContent = label || yes.dataset.defaultLabel;

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
