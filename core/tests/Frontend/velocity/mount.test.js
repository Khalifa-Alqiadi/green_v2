import { h } from 'vue';
import { afterEach, describe, expect, test, vi } from 'vitest';
import { mountVelocityIslands } from '../../../resources/themes/velocity/js/shared/mount.js';

afterEach(() => {
    document.body.innerHTML = '';
});

describe('Velocity island bootloader', () => {
    test('does not load Vue components when no island marker exists', async () => {
        const loader = vi.fn();

        await expect(mountVelocityIslands({ example: loader })).resolves.toEqual([]);
        expect(loader).not.toHaveBeenCalled();
    });

    test('mounts a supported island with JSON configuration', async () => {
        document.body.innerHTML = `
            <div data-velocity-component="example" data-velocity-config="example-config"></div>
            <script id="example-config" type="application/json">{"label":"Ready"}</script>
        `;

        const loader = vi.fn(async () => ({
            default: {
                props: ['label'],
                setup(props) {
                    return () => h('button', props.label);
                },
            },
        }));

        const apps = await mountVelocityIslands({ example: loader });
        const marker = document.querySelector('[data-velocity-component]');

        expect(apps).toHaveLength(1);
        expect(loader).toHaveBeenCalledOnce();
        expect(marker.dataset.velocityEnhancement).toBe('mounted');
        expect(marker.textContent).toBe('Ready');
    });

    test('leaves unsupported islands in a usable fallback state', async () => {
        document.body.innerHTML = '<div data-velocity-component="unknown">Native fallback</div>';

        await mountVelocityIslands({});

        const marker = document.querySelector('[data-velocity-component]');
        expect(marker.dataset.velocityEnhancement).toBe('unsupported');
        expect(marker.textContent).toBe('Native fallback');
    });
});
