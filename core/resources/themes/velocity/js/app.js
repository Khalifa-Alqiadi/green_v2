import '../css/app.css';
import { setupAppearance } from './modules/appearance.js';
import { setupGlobalUi } from './modules/global-ui.js';
import { setupDisclosures } from './modules/disclosures.js';
import { setupFormControls } from './modules/form-controls.js';
import { setupMotion } from './modules/motion.js';
import { setupNavigation } from './modules/navigation.js';
import { setupPopups } from './modules/popup.js';
import { enhanceVendorContent } from './modules/vendor-integrations.js';
import { mountVelocityIslands } from './shared/mount.js';

const componentLoaders = Object.freeze({
    'cms-form-enhancer': () => import('./components/CmsFormEnhancer.vue'),
    'topic-explorer': () => import('./components/TopicExplorer.vue'),
    'velocity-carousel': () => import('./components/VelocityCarousel.vue'),
});

export function enhanceVelocityContent(root = document) {
    const enhancements = [];

    if (root.querySelector('[data-velocity-chart]')) {
        enhancements.push(import('./modules/charts.js').then(({ enhanceCharts }) => enhanceCharts(root)));
    }
    if (root.querySelector('[data-velocity-map]')) {
        enhancements.push(import('./modules/maps.js').then(({ enhanceMaps }) => enhanceMaps(root)));
    }

    if (enhancements.length === 0) {
        return Promise.resolve(0);
    }

    return Promise.all(enhancements).then((counts) => counts.reduce((total, count) => total + count, 0));
}

export function bootVelocity(root = document) {
    setupAppearance(root);
    setupNavigation(root);
    setupGlobalUi(root);
    setupDisclosures(root);
    setupFormControls(root);
    setupMotion(root);
    setupPopups(root);
    void enhanceVelocityContent(root);
    void enhanceVendorContent(root);

    return mountVelocityIslands(componentLoaders, root);
}

if (typeof document !== 'undefined') {
    document.addEventListener('velocity:content-appended', (event) => {
        setupDisclosures(event.detail?.root ?? document);
        setupFormControls(event.detail?.root ?? document);
        setupMotion(event.detail?.root ?? document);
        void enhanceVelocityContent(event.detail?.root ?? document);
        void enhanceVendorContent(event.detail?.root ?? document);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => void bootVelocity(), { once: true });
    } else {
        void bootVelocity();
    }
}
