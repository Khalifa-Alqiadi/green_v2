function configElementFor(marker) {
    const configId = marker.dataset.velocityConfig;
    if (configId) {
        return marker.ownerDocument.getElementById(configId);
    }

    const sibling = marker.nextElementSibling;

    return sibling?.matches('script[type="application/json"][data-velocity-config]')
        ? sibling
        : null;
}

function readConfig(marker) {
    const element = configElementFor(marker);
    if (! element) {
        return {};
    }

    const config = JSON.parse(element.textContent ?? '{}');

    return config && typeof config === 'object' && ! Array.isArray(config) ? config : {};
}

export async function mountVelocityIsland(marker, loaders) {
    const name = marker.dataset.velocityComponent;
    const loadComponent = name ? loaders[name] : null;

    if (! loadComponent) {
        marker.dataset.velocityEnhancement = 'unsupported';

        return null;
    }

    marker.dataset.velocityEnhancement = 'loading';

    try {
        const [{ createApp }, componentModule] = await Promise.all([
            import('vue'),
            loadComponent(),
        ]);
        const app = createApp(componentModule.default, readConfig(marker));

        app.mount(marker);
        marker.dataset.velocityEnhancement = 'mounted';

        return app;
    } catch (error) {
        marker.dataset.velocityEnhancement = 'failed';
        console.error('[Velocity] Island enhancement failed.', { component: name, error });

        return null;
    }
}

export function mountVelocityIslands(loaders, root = document) {
    const markers = Array.from(root.querySelectorAll('[data-velocity-component]'));

    return Promise.all(markers.map((marker) => mountVelocityIsland(marker, loaders)));
}
