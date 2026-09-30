const storageKey = 'velocity-appearance';
const validModes = new Set(['light', 'dark']);

function storedMode() {
    try {
        const mode = window.localStorage.getItem(storageKey);

        return validModes.has(mode) ? mode : null;
    } catch {
        return null;
    }
}

function preferredMode() {
    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function setupAppearance(root = document) {
    const themeRoot = root.querySelector('[data-theme="velocity"]') ?? root.documentElement;
    const toggles = Array.from(root.querySelectorAll('[data-velocity-appearance-toggle]'));
    const configured = themeRoot.dataset.appearance;
    const locked = themeRoot.dataset.appearanceLocked === 'true';
    const mode = locked
        ? (validModes.has(configured) ? configured : 'light')
        : (storedMode() ?? (validModes.has(configured) ? configured : preferredMode()));

    themeRoot.dataset.appearance = mode;

    const syncToggles = (currentMode) => {
        toggles.forEach((button) => {
            button.setAttribute('aria-pressed', String(currentMode === 'dark'));
            updateLabel(button, currentMode);
        });
    };

    syncToggles(mode);
    toggles.forEach((button) => {
        button.addEventListener('click', () => {
            const nextMode = themeRoot.dataset.appearance === 'dark' ? 'light' : 'dark';
            themeRoot.dataset.appearance = nextMode;
            syncToggles(nextMode);

            try {
                window.localStorage.setItem(storageKey, nextMode);
            } catch {
                // Appearance remains available for this page when storage is blocked.
            }
        });
    });
}

function updateLabel(button, mode) {
    const label = button.querySelector('[data-velocity-appearance-label]');
    if (! label) {
        return;
    }

    label.textContent = mode === 'dark'
        ? (button.dataset.lightLabel ?? 'Light')
        : (button.dataset.darkLabel ?? 'Dark');
    button.setAttribute('aria-label', label.textContent);
}
