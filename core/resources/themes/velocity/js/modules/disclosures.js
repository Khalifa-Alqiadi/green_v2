const initialized = new WeakSet();

function finish(content) {
    content.style.removeProperty('height');
    content.style.removeProperty('opacity');
    content.style.removeProperty('overflow');
}

export function setupDisclosures(root = document) {
    const disclosures = Array.from(root.querySelectorAll('[data-velocity-disclosure]'))
        .filter((details) => ! initialized.has(details));
    const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

    disclosures.forEach((details) => {
        const summary = details.querySelector('summary');
        const content = summary?.nextElementSibling;
        if (! summary || ! content) {
            return;
        }

        initialized.add(details);
        content.dataset.velocityDisclosureContent = '';

        if (reduceMotion || typeof content.animate !== 'function') {
            return;
        }

        let animation = null;
        summary.addEventListener('click', (event) => {
            event.preventDefault();
            animation?.cancel();

            if (details.open) {
                const startHeight = `${content.getBoundingClientRect().height}px`;
                animation = content.animate([
                    { height: startHeight, opacity: 1 },
                    { height: '0px', opacity: 0 },
                ], {
                    duration: 320,
                    easing: 'cubic-bezier(0.2, 0.75, 0.25, 1)',
                });
                animation.addEventListener('finish', () => {
                    details.open = false;
                    finish(content);
                    animation = null;
                }, { once: true });
                return;
            }

            details.open = true;
            const endHeight = `${content.getBoundingClientRect().height}px`;
            animation = content.animate([
                { height: '0px', opacity: 0 },
                { height: endHeight, opacity: 1 },
            ], {
                duration: 360,
                easing: 'cubic-bezier(0.2, 0.75, 0.25, 1)',
            });
            animation.addEventListener('finish', () => {
                finish(content);
                animation = null;
            }, { once: true });
        });
    });

    return disclosures.length;
}
