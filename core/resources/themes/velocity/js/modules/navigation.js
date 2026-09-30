const initialized = new WeakSet();
const panelAnchors = new WeakMap();
const panelParents = new WeakMap();
const desktopQuery = '(min-width: 64rem)';
const stickyScrollOffset = 24;
const desktopStickyEnterOffset = 96;
const desktopStickyLeaveOffset = 16;
const submenuCloseDelay = 220;
const panelSelector = '[data-velocity-menu-panel]';
const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

function setupStickyHeaders(root) {
    root.querySelectorAll('[data-velocity-sticky-header]').forEach((header) => {
        if (initialized.has(header)) {
            return;
        }

        initialized.add(header);
        const media = window.matchMedia(desktopQuery);
        let isScrolled = header.dataset.scrolled === 'true';
        const update = () => {
            const threshold = media.matches
                ? (isScrolled ? desktopStickyLeaveOffset : desktopStickyEnterOffset)
                : stickyScrollOffset;
            const nextState = window.scrollY > threshold;

            if (nextState !== isScrolled || header.dataset.scrolled === undefined) {
                isScrolled = nextState;
                header.dataset.scrolled = String(nextState);
            }
        };
        window.addEventListener('scroll', update, { passive: true });
        media.addEventListener?.('change', update);
        update();
    });
}

function matchingCloseButtons(root, targetId) {
    return Array.from(root.querySelectorAll('[data-velocity-nav-close]'))
        .filter((button) => button.getAttribute('aria-controls') === targetId);
}

function controlledElement(root, button) {
    const targetId = button?.getAttribute('aria-controls');

    return targetId ? root.getElementById(targetId) : null;
}

function controllingButton(root, panel) {
    if (! panel?.id) {
        return null;
    }

    return Array.from(root.querySelectorAll('[data-velocity-submenu-toggle]'))
        .find((button) => button.getAttribute('aria-controls') === panel.id) ?? null;
}

function rootPanel(nav) {
    return nav.querySelector('[data-velocity-menu-root]');
}

function mobilePanelLabel(panel) {
    return panel?.dataset.velocityMenuLabel ?? '';
}

function setMobilePanelState(panel, state) {
    if (! panel) {
        return;
    }

    panel.dataset.velocityPanelState = state;
    panel.hidden = state === 'inactive';
    panel.inert = state !== 'active';

    if (state === 'active') {
        panel.removeAttribute('aria-hidden');
    } else {
        panel.setAttribute('aria-hidden', 'true');
    }
}

function updateMobileNavigationHead(nav, panel = rootPanel(nav)) {
    const root = rootPanel(nav);
    const title = nav.querySelector('[data-velocity-nav-title]');
    const context = nav.querySelector('[data-velocity-nav-context]');
    const rootBrand = nav.querySelector('[data-velocity-nav-root-brand]');
    const back = nav.querySelector('[data-velocity-nav-back]');
    const isRoot = ! panel || panel === root;

    if (title) {
        title.textContent = mobilePanelLabel(panel) || mobilePanelLabel(root);
    }
    if (back) {
        back.hidden = isRoot;
    }
    if (context) {
        context.hidden = isRoot;
    }
    if (rootBrand) {
        rootBrand.hidden = ! isRoot;
    }

    nav.dataset.velocityMenuDepth = isRoot ? '1' : (panel.dataset.velocityMenuLevel ?? '2');
}

function mountMobilePanels(nav) {
    if (nav.dataset.velocityMobilePanels === 'true') {
        return;
    }

    const viewport = nav.querySelector('.velocity-nav__viewport');
    const root = rootPanel(nav);
    if (! viewport || ! root) {
        return;
    }

    const submenus = Array.from(root.querySelectorAll(panelSelector)).filter((panel) => panel !== root);
    submenus.forEach((panel) => {
        if (! panelParents.has(panel)) {
            panelParents.set(panel, panel.parentElement?.closest(panelSelector) ?? root);
        }

        const anchor = document.createComment(`velocity-panel-${panel.id || 'submenu'}`);
        panel.parentNode?.insertBefore(anchor, panel);
        panelAnchors.set(panel, anchor);
        viewport.append(panel);
    });

    nav.dataset.velocityMobilePanels = 'true';
}

function restoreDesktopPanels(nav) {
    if (nav.dataset.velocityMobilePanels !== 'true') {
        return;
    }

    const root = rootPanel(nav);
    const panels = Array.from(nav.querySelectorAll(panelSelector)).filter((panel) => panel !== root);
    panels.forEach((panel) => {
        const anchor = panelAnchors.get(panel);
        if (anchor?.parentNode) {
            anchor.parentNode.insertBefore(panel, anchor);
            anchor.remove();
        }
        panelAnchors.delete(panel);
    });

    delete nav.dataset.velocityMobilePanels;
    root?.removeAttribute('aria-hidden');
}

function resetMobilePanels(nav) {
    mountMobilePanels(nav);

    const root = rootPanel(nav);
    nav.querySelectorAll('[data-velocity-submenu-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-expanded', 'false');
    });
    nav.querySelectorAll(panelSelector).forEach((panel) => {
        setMobilePanelState(panel, panel === root ? 'active' : 'inactive');
        delete panel.dataset.velocityFlipped;
    });
    updateMobileNavigationHead(nav, root);
}

function resetDesktopPanels(nav) {
    restoreDesktopPanels(nav);

    const root = rootPanel(nav);
    nav.querySelectorAll('[data-velocity-submenu-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-expanded', 'false');
    });
    nav.querySelectorAll(panelSelector).forEach((panel) => {
        delete panel.dataset.velocityPanelState;
        delete panel.dataset.velocityFlipped;
        panel.inert = false;
        panel.removeAttribute('aria-hidden');
        panel.hidden = panel !== root;
    });
}

function currentMobilePanel(nav) {
    return Array.from(nav.querySelectorAll(`${panelSelector}[data-velocity-panel-state="active"]`)).at(-1)
        ?? rootPanel(nav);
}

function directPanelFocusables(panel) {
    if (! panel) {
        return [];
    }

    return Array.from(panel.querySelectorAll(focusableSelector)).filter((element) => (
        element.closest(panelSelector) === panel
        && ! element.closest('[hidden]')
        && element.getAttribute('aria-hidden') !== 'true'
    ));
}

function enterMobilePanel(root, button, panel) {
    const nav = button.closest('.velocity-nav');
    if (! nav) {
        button.setAttribute('aria-expanded', 'true');
        panel.hidden = false;
        return;
    }

    const current = currentMobilePanel(nav);
    const parent = panelParents.get(panel) ?? button.closest(panelSelector) ?? current;

    nav.querySelectorAll(`${panelSelector}[data-velocity-panel-state="active"]`).forEach((activePanel) => {
        if (activePanel !== parent) {
            setMobilePanelState(activePanel, 'inactive');
        }
    });
    setMobilePanelState(parent, 'parent');
    setMobilePanelState(panel, 'active');
    button.setAttribute('aria-expanded', 'true');
    updateMobileNavigationHead(nav, panel);

    directPanelFocusables(panel)[0]?.focus();
}

function leaveMobilePanel(root, nav, restoreFocus = true) {
    if (! nav) {
        return false;
    }

    const current = currentMobilePanel(nav);
    const rootMenu = rootPanel(nav);
    if (! current || current === rootMenu) {
        return false;
    }

    const parent = panelParents.get(current) ?? rootMenu;
    const controller = controllingButton(root, current);
    controller?.setAttribute('aria-expanded', 'false');
    setMobilePanelState(current, 'inactive');
    setMobilePanelState(parent, 'active');
    updateMobileNavigationHead(nav, parent);

    if (restoreFocus) {
        controller?.focus();
    }

    return true;
}

function setupPrimaryNavigation(root, button) {
    const targetId = button.getAttribute('aria-controls');
    const target = targetId ? root.getElementById(targetId) : null;
    if (! target) {
        return;
    }

    initialized.add(button);
    const media = window.matchMedia(desktopQuery);
    const closeButtons = matchingCloseButtons(root, targetId);
    const backdrop = closeButtons.find((item) => item.classList.contains('velocity-nav__backdrop'));
    const backButton = target.querySelector('[data-velocity-nav-back]');
    const html = root.documentElement ?? document.documentElement;

    const close = (restoreFocus = false) => {
        if (media.matches) {
            return;
        }

        button.setAttribute('aria-expanded', 'false');
        target.hidden = true;
        if (backdrop) {
            backdrop.hidden = true;
        }
        delete html.dataset.velocityNavOpen;
        resetMobilePanels(target);
        if (restoreFocus) {
            button.focus();
        }
    };

    const open = () => {
        resetMobilePanels(target);
        button.setAttribute('aria-expanded', 'true');
        target.hidden = false;
        if (backdrop) {
            backdrop.hidden = false;
        }
        html.dataset.velocityNavOpen = 'true';
        target.querySelector('[data-velocity-nav-close]')?.focus();
    };

    const sync = () => {
        delete html.dataset.velocityNavOpen;
        if (media.matches) {
            resetDesktopPanels(target);
            button.hidden = true;
            button.setAttribute('aria-expanded', 'true');
            target.hidden = false;
            if (backdrop) {
                backdrop.hidden = true;
            }
            return;
        }

        button.hidden = false;
        resetMobilePanels(target);
        button.setAttribute('aria-expanded', 'false');
        target.hidden = true;
        if (backdrop) {
            backdrop.hidden = true;
        }
    };

    button.addEventListener('click', () => {
        if (button.getAttribute('aria-expanded') === 'true') {
            close(true);
        } else {
            open();
        }
    });
    closeButtons.forEach((closeButton) => closeButton.addEventListener('click', () => close(true)));
    backButton?.addEventListener('click', () => leaveMobilePanel(root, target, true));
    target.addEventListener('click', (event) => {
        if (! media.matches && event.target.closest('a[href]:not([href="#"])')) {
            close(false);
        }
    });
    target.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            if (! media.matches && leaveMobilePanel(root, target, true)) {
                return;
            }
            close(true);
            return;
        }
        if (event.key !== 'Tab' || media.matches) {
            return;
        }

        const headerFocusables = Array.from(target.querySelectorAll('.velocity-nav__mobile-head a[href], .velocity-nav__mobile-head button:not([disabled])'))
            .filter((element) => ! element.hidden && ! element.closest('[hidden]'));
        const footerFocusables = Array.from(target.querySelectorAll('.velocity-nav__mobile-footer a[href], .velocity-nav__mobile-footer button:not([disabled])'))
            .filter((element) => ! element.closest('[hidden]'));
        const focusable = [
            ...headerFocusables,
            ...directPanelFocusables(currentMobilePanel(target)),
            ...footerFocusables,
        ];
        const first = focusable[0];
        const last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });

    media.addEventListener?.('change', sync);
    sync();
}

function closeSubmenuTree(root, button, restoreFocus = false) {
    const target = controlledElement(root, button);
    if (! target) {
        return;
    }

    button.setAttribute('aria-expanded', 'false');
    target.hidden = true;
    delete target.dataset.velocityFlipped;
    target.querySelectorAll('[data-velocity-submenu-toggle]').forEach((nestedToggle) => {
        nestedToggle.setAttribute('aria-expanded', 'false');
        const nestedPanel = controlledElement(root, nestedToggle);
        if (nestedPanel) {
            nestedPanel.hidden = true;
            delete nestedPanel.dataset.velocityFlipped;
        }
    });

    if (restoreFocus) {
        button.focus();
    }
}

function closeDesktopSiblings(root, button) {
    const panel = button.closest(panelSelector);
    if (! panel) {
        return;
    }

    Array.from(panel.children).forEach((item) => {
        const siblingButton = item.querySelector(':scope > .velocity-menu__row > [data-velocity-submenu-toggle]');
        if (siblingButton && siblingButton !== button) {
            closeSubmenuTree(root, siblingButton, false);
        }
    });
}

function alignDesktopSubmenu(panel) {
    delete panel.dataset.velocityFlipped;
    const rect = panel.getBoundingClientRect();
    const viewportWidth = document.documentElement.clientWidth || window.innerWidth;
    const rtl = document.documentElement.dir === 'rtl';
    const crossesInlineEnd = rtl ? rect.left < 16 : rect.right > viewportWidth - 16;

    if (crossesInlineEnd) {
        panel.dataset.velocityFlipped = 'true';
    }
}

function setupSubmenu(root, button) {
    const target = controlledElement(root, button);
    if (! target) {
        return;
    }

    initialized.add(button);
    const media = window.matchMedia(desktopQuery);
    const item = button.closest('[data-velocity-menu-item]');
    const parentLink = item?.querySelector(':scope > .velocity-menu__row > .velocity-menu__link');
    let closeTimer = null;
    const cancelClose = () => {
        if (closeTimer !== null) {
            window.clearTimeout(closeTimer);
            closeTimer = null;
        }
    };
    const close = (restoreFocus = false) => {
        cancelClose();
        closeSubmenuTree(root, button, restoreFocus);
    };
    const open = () => {
        cancelClose();
        if (! media.matches) {
            enterMobilePanel(root, button, target);
            return;
        }

        closeDesktopSiblings(root, button);
        button.setAttribute('aria-expanded', 'true');
        target.hidden = false;
        alignDesktopSubmenu(target);
    };
    const scheduleClose = () => {
        cancelClose();
        closeTimer = window.setTimeout(() => close(false), submenuCloseDelay);
    };

    button.hidden = false;
    close(false);
    button.addEventListener('click', () => {
        if (button.getAttribute('aria-expanded') === 'true') {
            if (! media.matches) {
                const nav = button.closest('.velocity-nav');
                if (! leaveMobilePanel(root, nav, false)) {
                    close(false);
                }
            } else {
                close(false);
            }
        } else {
            open();
        }
    });
    parentLink?.addEventListener('click', (event) => {
        if (! media.matches && parentLink.getAttribute('href') === '#') {
            event.preventDefault();
            open();
        }
    });
    target.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (! media.matches) {
            const nav = button.closest('.velocity-nav');
            if (! leaveMobilePanel(root, nav, true)) {
                close(true);
            }
        } else {
            close(true);
        }
    });
    item?.addEventListener('pointerenter', () => {
        if (media.matches) {
            open();
        }
    });
    item?.addEventListener('pointerleave', () => {
        if (media.matches) {
            scheduleClose();
        }
    });
    target.addEventListener('pointerenter', cancelClose);
    item?.addEventListener('focusout', (event) => {
        if (media.matches && ! item.contains(event.relatedTarget)) {
            close(false);
        }
    });
}

function markActiveTrails(root) {
    root.querySelectorAll('.velocity-menu__link.active, .velocity-menu__link[aria-current="page"]').forEach((link) => {
        let item = link.closest('[data-velocity-menu-item]');
        while (item) {
            item.dataset.velocityActiveTrail = 'true';
            item = item.parentElement?.closest('[data-velocity-menu-item]') ?? null;
        }
    });
}

export function setupNavigation(root = document) {
    setupStickyHeaders(root);
    markActiveTrails(root);

    root.querySelectorAll('[data-velocity-nav-toggle]').forEach((button) => {
        if (! initialized.has(button)) {
            setupPrimaryNavigation(root, button);
        }
    });

    root.querySelectorAll('[data-velocity-submenu-toggle]').forEach((button) => {
        if (! initialized.has(button)) {
            setupSubmenu(root, button);
        }
    });

}
