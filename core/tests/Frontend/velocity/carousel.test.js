import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, test, vi } from 'vitest';
import VelocityCarousel from '../../../resources/themes/velocity/js/components/VelocityCarousel.vue';

afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
});

describe('Velocity carousel enhancement', () => {
    function pointerEvent(type, properties) {
        const event = new Event(type, { bubbles: true, cancelable: true });
        Object.defineProperties(event, Object.fromEntries(
            Object.entries(properties).map(([key, value]) => [key, { value }]),
        ));

        return event;
    }

    test('moves through Blade-rendered slides without owning their content', async () => {
        document.body.innerHTML = `
            <div id="carousel">
                <article data-velocity-carousel-slide>One</article>
                <article data-velocity-carousel-slide>Two</article>
            </div>
        `;
        window.matchMedia = vi.fn(() => ({ matches: false }));
        const slides = Array.from(document.querySelectorAll('[data-velocity-carousel-slide]'));
        const track = document.getElementById('carousel');
        track.scrollBy = vi.fn();
        track.getBoundingClientRect = () => ({ left: 0, right: 300 });
        slides.forEach((slide, index) => {
            slide.getBoundingClientRect = () => ({ left: index * 100, right: (index + 1) * 100 });
        });

        const wrapper = mount(VelocityCarousel, {
            props: { target: 'carousel', count: 2, nextLabel: 'Next slide' },
        });

        expect(wrapper.find('.velocity-carousel__status').exists()).toBe(false);
        await wrapper.get('button[aria-label="Next slide"]').trigger('click');
        expect(track.scrollBy).toHaveBeenCalledWith({ left: 100, behavior: 'smooth' });
        expect(wrapper.find('button[aria-label="Pause"]').exists()).toBe(false);
    });

    test('does not autoplay when reduced motion is requested', () => {
        document.body.innerHTML = '<div id="carousel"><div data-velocity-carousel-slide></div><div data-velocity-carousel-slide></div></div>';
        window.matchMedia = vi.fn(() => ({ matches: true }));
        const intervalSpy = vi.spyOn(window, 'setInterval');

        mount(VelocityCarousel, {
            props: { target: 'carousel', count: 2, autoplay: true },
        });

        expect(intervalSpy).not.toHaveBeenCalled();
    });

    test('supports pointer dragging and suppresses an accidental link click', () => {
        document.body.innerHTML = `
            <div id="carousel">
                <article data-velocity-carousel-slide><a href="/one">One</a></article>
                <article data-velocity-carousel-slide>Two</article>
            </div>
        `;
        window.matchMedia = vi.fn(() => ({ matches: false }));
        const track = document.getElementById('carousel');
        const slides = Array.from(track.querySelectorAll('[data-velocity-carousel-slide]'));
        track.scrollBy = vi.fn();
        track.setPointerCapture = vi.fn();
        track.releasePointerCapture = vi.fn();
        track.getBoundingClientRect = () => ({ left: 0, right: 300 });
        slides.forEach((slide, index) => {
            slide.getBoundingClientRect = () => ({ left: index * 100, right: (index + 1) * 100 });
        });

        mount(VelocityCarousel, { props: { target: 'carousel', count: 2 } });

        track.dispatchEvent(pointerEvent('pointerdown', {
            pointerId: 7, pointerType: 'mouse', isPrimary: true, button: 0, clientX: 180,
        }));
        track.dispatchEvent(pointerEvent('pointermove', {
            pointerId: 7, clientX: 130,
        }));

        expect(track.classList.contains('is-dragging')).toBe(true);
        expect(track.scrollBy).toHaveBeenCalledWith({ left: 50, behavior: 'auto' });

        track.dispatchEvent(pointerEvent('pointerup', { pointerId: 7, clientX: 130 }));
        const click = new MouseEvent('click', { bubbles: true, cancelable: true });
        track.querySelector('a').dispatchEvent(click);

        expect(track.classList.contains('is-dragging')).toBe(false);
        expect(click.defaultPrevented).toBe(true);
        expect(track.releasePointerCapture).toHaveBeenCalledWith(7);
    });
});
