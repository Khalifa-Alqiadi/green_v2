import { beforeEach, describe, expect, it, vi } from 'vitest';
import { setupAppearance } from '../../../resources/themes/velocity/js/modules/appearance.js';
import { setupGlobalUi } from '../../../resources/themes/velocity/js/modules/global-ui.js';
import { setupNavigation } from '../../../resources/themes/velocity/js/modules/navigation.js';
import { setupPopups } from '../../../resources/themes/velocity/js/modules/popup.js';

describe('Velocity global shell enhancements', () => {
    beforeEach(() => {
        vi.useRealTimers();
        document.documentElement.dataset.theme = 'velocity';
        document.documentElement.dataset.appearance = 'light';
        document.documentElement.dataset.appearanceLocked = 'false';
        document.body.innerHTML = '';
        window.localStorage.clear();
        window.matchMedia = vi.fn(() => ({
            matches: false,
            addEventListener: vi.fn(),
        }));
    });

    it('keeps the desktop sticky header stable while its height changes near the scroll boundary', () => {
        let scrollY = 0;
        vi.spyOn(window, 'scrollY', 'get').mockImplementation(() => scrollY);
        window.matchMedia = vi.fn(() => ({
            matches: true,
            addEventListener: vi.fn(),
        }));
        document.body.innerHTML = '<header data-velocity-sticky-header></header>';

        setupNavigation(document);
        const header = document.querySelector('[data-velocity-sticky-header]');

        expect(header.dataset.scrolled).toBe('false');

        scrollY = 100;
        window.dispatchEvent(new Event('scroll'));
        expect(header.dataset.scrolled).toBe('true');

        scrollY = 40;
        window.dispatchEvent(new Event('scroll'));
        expect(header.dataset.scrolled).toBe('true');

        scrollY = 10;
        window.dispatchEvent(new Event('scroll'));
        expect(header.dataset.scrolled).toBe('false');

        scrollY = 80;
        window.dispatchEvent(new Event('scroll'));
        expect(header.dataset.scrolled).toBe('false');
    });

    it('keeps a desktop submenu open while the pointer crosses into its panel', () => {
        vi.useFakeTimers();
        window.matchMedia = vi.fn(() => ({
            matches: true,
            addEventListener: vi.fn(),
        }));
        document.body.innerHTML = `
            <li data-velocity-menu-item>
                <button data-velocity-submenu-toggle aria-controls="desktop-submenu"></button>
                <ul id="desktop-submenu"><li><a href="/services/local">Local</a></li></ul>
            </li>
        `;

        setupNavigation(document);

        const item = document.querySelector('[data-velocity-menu-item]');
        const submenu = document.querySelector('#desktop-submenu');
        item.dispatchEvent(new Event('pointerenter'));
        expect(submenu.hidden).toBe(false);

        item.dispatchEvent(new Event('pointerleave'));
        vi.advanceTimersByTime(150);
        expect(submenu.hidden).toBe(false);

        submenu.dispatchEvent(new Event('pointerenter'));
        vi.advanceTimersByTime(220);
        expect(submenu.hidden).toBe(false);

        item.dispatchEvent(new Event('pointerleave'));
        vi.advanceTimersByTime(220);
        expect(submenu.hidden).toBe(true);
    });

    it('enhances the mobile and nested navigation without replacing its links', () => {
        document.body.innerHTML = `
            <button data-velocity-nav-toggle aria-controls="main-menu"></button>
            <nav id="main-menu"><a href="/news">News</a></nav>
            <button data-velocity-submenu-toggle aria-controls="submenu"></button>
            <ul id="submenu"><li><a href="/news/local">Local</a></li></ul>
        `;

        setupNavigation(document);

        const navigation = document.querySelector('#main-menu');
        const navigationToggle = document.querySelector('[data-velocity-nav-toggle]');
        const submenu = document.querySelector('#submenu');
        const submenuToggle = document.querySelector('[data-velocity-submenu-toggle]');

        expect(navigation.hidden).toBe(true);
        expect(submenu.hidden).toBe(true);
        expect(document.querySelector('a[href="/news/local"]')).not.toBeNull();

        navigationToggle.click();
        submenuToggle.click();

        expect(navigation.hidden).toBe(false);
        expect(navigationToggle.getAttribute('aria-expanded')).toBe('true');
        expect(submenu.hidden).toBe(false);
        expect(submenuToggle.getAttribute('aria-expanded')).toBe('true');

        submenu.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        navigation.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

        expect(submenu.hidden).toBe(true);
        expect(submenuToggle.getAttribute('aria-expanded')).toBe('false');
        expect(navigation.hidden).toBe(true);
        expect(navigationToggle.getAttribute('aria-expanded')).toBe('false');
    });

    it('drills through three mobile menu levels and returns one panel at a time', () => {
        document.body.innerHTML = `
            <button data-velocity-nav-toggle aria-controls="app-menu"></button>
            <nav class="velocity-nav" id="app-menu">
                <div class="velocity-nav__mobile-head">
                    <button data-velocity-nav-back hidden>Back</button>
                    <a href="/" data-velocity-nav-root-brand>Logo</a>
                    <div data-velocity-nav-context hidden><strong data-velocity-nav-title>Menu</strong></div>
                    <button data-velocity-nav-close aria-controls="app-menu">Close</button>
                </div>
                <div class="velocity-nav__viewport">
                    <ul data-velocity-menu-panel data-velocity-menu-root data-velocity-menu-label="Menu">
                        <li data-velocity-menu-item>
                            <div class="velocity-menu__row">
                                <a class="velocity-menu__link" href="/products">Products</a>
                                <button data-velocity-submenu-toggle aria-controls="products-panel"></button>
                            </div>
                            <ul id="products-panel" data-velocity-menu-panel data-velocity-menu-level="2" data-velocity-menu-label="Products">
                                <li data-velocity-menu-item>
                                    <div class="velocity-menu__row">
                                        <a class="velocity-menu__link" href="/products/books">Books</a>
                                        <button data-velocity-submenu-toggle aria-controls="books-panel"></button>
                                    </div>
                                    <ul id="books-panel" data-velocity-menu-panel data-velocity-menu-level="3" data-velocity-menu-label="Books">
                                        <li data-velocity-menu-item>
                                            <div class="velocity-menu__row"><a class="velocity-menu__link" href="/products/books/kids">Kids</a></div>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div class="velocity-nav__mobile-footer"><a href="/contact">Contact</a></div>
            </nav>
            <button data-velocity-nav-close aria-controls="app-menu"></button>
        `;

        setupNavigation(document);

        const navToggle = document.querySelector('[data-velocity-nav-toggle]');
        const nav = document.querySelector('#app-menu');
        const viewport = nav.querySelector('.velocity-nav__viewport');
        const rootPanel = nav.querySelector('[data-velocity-menu-root]');
        const productsPanel = document.querySelector('#products-panel');
        const booksPanel = document.querySelector('#books-panel');
        const [productsToggle, booksToggle] = document.querySelectorAll('[data-velocity-submenu-toggle]');
        const back = nav.querySelector('[data-velocity-nav-back]');
        const rootBrand = nav.querySelector('[data-velocity-nav-root-brand]');
        const context = nav.querySelector('[data-velocity-nav-context]');
        const title = nav.querySelector('[data-velocity-nav-title]');

        expect(productsPanel.parentElement).toBe(viewport);
        expect(booksPanel.parentElement).toBe(viewport);
        navToggle.click();
        productsToggle.click();

        expect(rootPanel.dataset.velocityPanelState).toBe('parent');
        expect(productsPanel.dataset.velocityPanelState).toBe('active');
        expect(title.textContent).toBe('Products');
        expect(back.hidden).toBe(false);
        expect(rootBrand.hidden).toBe(true);
        expect(context.hidden).toBe(false);

        booksToggle.click();

        expect(productsPanel.dataset.velocityPanelState).toBe('parent');
        expect(booksPanel.dataset.velocityPanelState).toBe('active');
        expect(title.textContent).toBe('Books');

        back.click();

        expect(productsPanel.dataset.velocityPanelState).toBe('active');
        expect(booksPanel.hidden).toBe(true);
        expect(title.textContent).toBe('Products');

        back.click();

        expect(rootPanel.dataset.velocityPanelState).toBe('active');
        expect(productsPanel.hidden).toBe(true);
        expect(back.hidden).toBe(true);
        expect(title.textContent).toBe('Menu');
        expect(rootBrand.hidden).toBe(false);
        expect(context.hidden).toBe(true);
    });

    it('stores and announces an appearance change', () => {
        document.body.innerHTML = `
            <button
                data-velocity-appearance-toggle
                data-light-label="Light"
                data-dark-label="Dark"
            ><span data-velocity-appearance-label></span></button>
        `;

        setupAppearance(document);
        const toggle = document.querySelector('button');
        toggle.click();

        expect(document.documentElement.dataset.appearance).toBe('dark');
        expect(toggle.getAttribute('aria-pressed')).toBe('true');
        expect(toggle.textContent).toContain('Light');
        expect(window.localStorage.getItem('velocity-appearance')).toBe('dark');
    });

    it('accepts the cookie notice and removes the preloader', () => {
        document.body.innerHTML = `
            <aside data-velocity-cookie><button data-velocity-cookie-accept>Accept</button></aside>
            <div data-velocity-preloader></div>
        `;

        setupGlobalUi(document);
        document.querySelector('[data-velocity-cookie-accept]').click();

        expect(document.querySelector('[data-velocity-cookie]').hidden).toBe(true);
        expect(document.querySelector('[data-velocity-preloader]')).toBeNull();
    });

    it('opens and closes the WhatsApp card without replacing its direct chat link', () => {
        document.body.innerHTML = `
            <div data-velocity-whatsapp>
                <section id="whatsapp-card" data-velocity-whatsapp-panel hidden>
                    <button data-velocity-whatsapp-close>Close</button>
                </section>
                <a
                    href="https://wa.me/201234567890"
                    aria-controls="whatsapp-card"
                    aria-expanded="false"
                    data-open-label="Open chat"
                    data-close-label="Close chat"
                    data-velocity-whatsapp-toggle
                >WhatsApp</a>
            </div>
        `;

        setupGlobalUi(document);
        const panel = document.querySelector('[data-velocity-whatsapp-panel]');
        const closeButton = document.querySelector('[data-velocity-whatsapp-close]');
        const launcher = document.querySelector('[data-velocity-whatsapp-toggle]');

        launcher.click();

        expect(panel.hidden).toBe(false);
        expect(launcher.getAttribute('href')).toBe('https://wa.me/201234567890');
        expect(launcher.getAttribute('aria-expanded')).toBe('true');
        expect(document.activeElement).toBe(closeButton);

        closeButton.click();

        expect(panel.hidden).toBe(true);
        expect(launcher.getAttribute('aria-expanded')).toBe('false');
        expect(document.activeElement).toBe(launcher);

        launcher.click();
        panel.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

        expect(panel.hidden).toBe(true);
    });

    it('keeps popup content in the document and initializes the dialog', () => {
        document.body.innerHTML = `
            <dialog data-velocity-popup data-delay="0" data-closable="true" open>
                <h2>Server-rendered offer</h2>
            </dialog>
        `;

        const count = setupPopups(document);

        expect(count).toBe(1);
        expect(document.querySelector('[data-velocity-popup]').open).toBe(true);
        expect(document.body.textContent).toContain('Server-rendered offer');
    });

    it('promotes an immediately open popup to the centered modal top layer', () => {
        document.body.innerHTML = '<dialog data-velocity-popup data-delay="0" data-closable="true" open></dialog>';
        const popup = document.querySelector('[data-velocity-popup]');
        const showModal = vi.fn(() => {
            popup.setAttribute('open', '');
        });
        Object.defineProperty(popup, 'showModal', { value: showModal });

        setupPopups(document);

        expect(showModal).toHaveBeenCalledOnce();
        expect(popup.open).toBe(true);
    });
});
