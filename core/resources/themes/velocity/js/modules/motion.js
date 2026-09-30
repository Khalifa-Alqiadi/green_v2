const initialized = new WeakSet();
let observer = null;

function reveal(element) {
    element.dataset.velocityRevealReady = 'true';
}

function motionObserver() {
    if (observer || typeof window.IntersectionObserver !== 'function') {
        return observer;
    }

    observer = new window.IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            reveal(entry.target);
            observer.unobserve(entry.target);
        });
    }, {
        rootMargin: '0px 0px -8% 0px',
        threshold: 0.08,
    });

    return observer;
}

export function setupMotion(root = document) {
    const elements = Array.from(root.querySelectorAll('[data-velocity-reveal]'))
        .filter((element) => ! initialized.has(element));
    const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const currentObserver = reduceMotion ? null : motionObserver();

    elements.forEach((element) => {
        initialized.add(element);
        if (! currentObserver) {
            reveal(element);
            return;
        }

        currentObserver.observe(element);
    });

    return elements.length;
}
