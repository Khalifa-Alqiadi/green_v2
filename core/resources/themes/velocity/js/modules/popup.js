const initialized = new WeakSet();

export function setupPopups(root = document) {
    const popups = root.querySelectorAll('[data-velocity-popup]');

    popups.forEach((popup) => {
        if (initialized.has(popup)) {
            return;
        }

        initialized.add(popup);
        const delay = Number.parseInt(popup.dataset.delay ?? '0', 10);
        const open = () => {
            if (typeof popup.showModal === 'function') {
                // The server-rendered `open` attribute keeps the content available
                // without JavaScript, but an already-open dialog cannot be promoted
                // to the modal top layer. Reset it before opening it as a true modal.
                if (popup.open) {
                    popup.removeAttribute('open');
                }

                try {
                    popup.showModal();
                    popup.focus({ preventScroll: true });
                    return;
                } catch {
                    // Keep the popup usable in browsers without complete dialog support.
                }
            }

            if (! popup.open) {
                popup.setAttribute('open', '');
            }
        };

        if (delay > 0) {
            popup.removeAttribute('open');
            window.setTimeout(open, delay);
        } else {
            open();
        }

        popup.addEventListener('click', (event) => {
            if (event.target === popup && popup.dataset.closable === 'true') {
                popup.close?.();
            }
        });

        popup.addEventListener('cancel', (event) => {
            if (popup.dataset.closable !== 'true') {
                event.preventDefault();
            }
        });
    });

    return popups.length;
}
