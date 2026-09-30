const initialized = new WeakSet();

export function setupGlobalUi(root = document) {
    setupDialogs(root);
    setupCookieNotices(root);
    setupDismissButtons(root);
    setupWhatsAppWidgets(root);

    root.querySelectorAll('[data-velocity-preloader]').forEach((preloader) => {
        preloader.remove();
    });
}

function setupDialogs(root) {
    root.querySelectorAll('[data-velocity-dialog-open]').forEach((button) => {
        if (initialized.has(button)) {
            return;
        }

        const target = root.getElementById(button.dataset.velocityDialogOpen);
        if (! target) {
            return;
        }

        initialized.add(button);
        button.addEventListener('click', () => {
            if (typeof target.showModal === 'function') {
                target.showModal();
            } else {
                target.setAttribute('open', '');
            }

            target.querySelector('[autofocus], input, button, select, textarea')?.focus();
        });
    });
}

function setupCookieNotices(root) {
    root.querySelectorAll('[data-velocity-cookie]').forEach((notice) => {
        if (initialized.has(notice)) {
            return;
        }

        initialized.add(notice);
        if (document.cookie.split('; ').includes('cookie_notice_accepted=true')) {
            notice.hidden = true;
            return;
        }

        notice.querySelector('[data-velocity-cookie-accept]')?.addEventListener('click', () => {
            document.cookie = 'cookie_notice_accepted=true; Max-Age=31536000; Path=/; SameSite=Lax';
            notice.hidden = true;
        });
    });
}

function setupDismissButtons(root) {
    root.querySelectorAll('[data-velocity-dismiss]').forEach((button) => {
        if (initialized.has(button)) {
            return;
        }

        initialized.add(button);
        button.addEventListener('click', () => {
            button.closest('[data-velocity-dismissible]')?.remove();
        });
    });
}

function setupWhatsAppWidgets(root) {
    root.querySelectorAll('[data-velocity-whatsapp]').forEach((widget) => {
        if (initialized.has(widget)) {
            return;
        }

        const launcher = widget.querySelector('[data-velocity-whatsapp-toggle]');
        const panel = widget.querySelector('[data-velocity-whatsapp-panel]');
        const closeButton = widget.querySelector('[data-velocity-whatsapp-close]');

        if (! launcher || ! panel) {
            return;
        }

        initialized.add(widget);

        const hidePanel = (returnFocus = true) => {
            panel.hidden = true;
            launcher.setAttribute('aria-expanded', 'false');
            launcher.setAttribute('aria-label', launcher.dataset.openLabel || 'WhatsApp');

            if (returnFocus) {
                launcher.focus();
            }
        };

        const showPanel = () => {
            panel.hidden = false;
            launcher.setAttribute('aria-expanded', 'true');
            launcher.setAttribute('aria-label', launcher.dataset.closeLabel || 'WhatsApp');
            closeButton?.focus();
        };

        launcher.addEventListener('click', (event) => {
            event.preventDefault();

            if (panel.hidden) {
                showPanel();
            } else {
                hidePanel(false);
            }
        });

        closeButton?.addEventListener('click', () => hidePanel());
        panel.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                hidePanel();
            }
        });
    });
}
