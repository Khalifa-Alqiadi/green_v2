import { afterEach, describe, expect, test, vi } from 'vitest';
import { enhanceMaps } from '../../../resources/themes/velocity/js/modules/maps.js';

afterEach(() => {
    document.body.innerHTML = '';
    delete window.google;
    delete window.IntersectionObserver;
    vi.restoreAllMocks();
});

describe('Velocity map loading', () => {
    test('defers the external API until the map is near the viewport', async () => {
        let intersectionCallback;
        window.IntersectionObserver = class {
            constructor(callback) {
                intersectionCallback = callback;
            }

            observe() {}

            disconnect() {}
        };

        const mapConstructor = vi.fn();
        const markerConstructor = vi.fn();
        const informationConstructor = vi.fn();
        class MapMock {
            constructor(...arguments_) {
                mapConstructor(...arguments_);
            }
        }
        class MarkerMock {
            constructor(...arguments_) {
                markerConstructor(...arguments_);
            }

            addListener() {}
        }
        class InformationMock {
            constructor(...arguments_) {
                informationConstructor(...arguments_);
            }

            open() {}
        }
        window.google = {
            maps: {
                Map: MapMock,
                Marker: MarkerMock,
                InfoWindow: InformationMock,
            },
        };
        document.body.innerHTML = `
            <div id="map" data-velocity-map aria-hidden="true"></div>
            <script type="application/json" data-velocity-map-config>
                {"apiUrl":"https://maps.example.test/api.js","locations":[{"latitude":30,"longitude":31,"title":"Cairo"}]}
            </script>
        `;

        const enhancement = enhanceMaps();
        await Promise.resolve();

        expect(mapConstructor).not.toHaveBeenCalled();
        expect(document.getElementById('map').dataset.velocityMapReady).toBe('pending');

        intersectionCallback([{ isIntersecting: true }]);
        await enhancement;

        expect(mapConstructor).toHaveBeenCalledOnce();
        expect(document.getElementById('map').dataset.velocityMapReady).toBe('true');
        expect(document.getElementById('map').hasAttribute('aria-hidden')).toBe(false);
    });
});
