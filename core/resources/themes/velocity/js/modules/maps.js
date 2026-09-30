let mapsLoader = null;

function loadMapsApi(apiUrl) {
    if (window.google?.maps) {
        return Promise.resolve(window.google.maps);
    }

    if (! mapsLoader) {
        mapsLoader = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = apiUrl;
            script.async = true;
            script.defer = true;
            script.addEventListener('load', () => resolve(window.google?.maps), { once: true });
            script.addEventListener('error', reject, { once: true });
            document.head.append(script);
        });
    }

    return mapsLoader;
}

function readMapConfig(map) {
    const config = map.nextElementSibling;
    if (! config?.matches('script[data-velocity-map-config]')) {
        return null;
    }

    try {
        return JSON.parse(config.textContent ?? '{}');
    } catch {
        return null;
    }
}

function waitUntilNearViewport(map) {
    if (typeof window.IntersectionObserver !== 'function') {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        const observer = new window.IntersectionObserver((entries) => {
            if (! entries.some((entry) => entry.isIntersecting)) {
                return;
            }

            observer.disconnect();
            resolve();
        }, { rootMargin: '400px 0px' });

        observer.observe(map);
    });
}

export async function enhanceMaps(root = document) {
    const maps = Array.from(root.querySelectorAll('[data-velocity-map]'))
        .filter((map) => ! ['pending', 'true'].includes(map.dataset.velocityMapReady));

    await Promise.all(maps.map(async (map) => {
        const config = readMapConfig(map);
        if (! config?.apiUrl || ! Array.isArray(config.locations) || config.locations.length === 0) {
            return;
        }

        map.dataset.velocityMapReady = 'pending';
        await waitUntilNearViewport(map);

        try {
            const googleMaps = await loadMapsApi(config.apiUrl);
            if (! googleMaps) {
                return;
            }

            const center = config.locations[0];
            const instance = new googleMaps.Map(map, {
                center: { lat: Number(center.latitude), lng: Number(center.longitude) },
                zoom: 6,
                mapTypeControl: false,
                streetViewControl: false,
            });

            config.locations.forEach((location) => {
                const marker = new googleMaps.Marker({
                    map: instance,
                    position: { lat: Number(location.latitude), lng: Number(location.longitude) },
                    title: location.title,
                });
                const information = new googleMaps.InfoWindow({
                    content: `<strong>${String(location.title ?? '').replace(/[&<>"']/g, '')}</strong>`,
                });
                marker.addListener('click', () => information.open({ map: instance, anchor: marker }));
            });

            map.dataset.velocityMapReady = 'true';
            map.removeAttribute('aria-hidden');
        } catch {
            map.dataset.velocityMapReady = 'failed';
        }
    }));

    return maps.length;
}
