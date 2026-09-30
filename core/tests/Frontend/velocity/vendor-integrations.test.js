import { afterEach, describe, expect, test, vi } from 'vitest';
import {
    enhanceVendorContent,
    loadScript,
    loadStyle,
} from '../../../resources/themes/velocity/js/modules/vendor-integrations.js';

function installVendorMocks() {
    const append = document.head.append.bind(document.head);
    return vi.spyOn(document.head, 'append').mockImplementation((node) => {
        append(node);
        if (node.dataset.velocityVendorScript?.includes('intl-tel-input')) {
            window.intlTelInput = vi.fn((input) => ({
                getNumber: () => `+2010${input.value.replace(/\D/g, '')}`,
                setNumber: vi.fn((value) => { input.value = value; }),
            }));
        }
        if (node.dataset.velocityVendorScript?.includes('green-audio-player')) {
            window.GreenAudioPlayer = vi.fn();
        }
        if (node.dataset.velocityVendorScript?.includes('video-js')) {
            window.videojs = vi.fn(() => ({ dispose: vi.fn() }));
        }
        if (node.dataset.velocityVendorScript?.includes('glightbox')) {
            window.GLightbox = vi.fn(() => ({ reload: vi.fn(), destroy: vi.fn() }));
        }
        queueMicrotask(() => node.dispatchEvent(new Event('load')));
        return node;
    });
}

afterEach(() => {
    document.body.innerHTML = '';
    document.head.querySelectorAll('[data-velocity-vendor-style], [data-velocity-vendor-script]').forEach((node) => node.remove());
    delete window.intlTelInput;
    delete window.GreenAudioPlayer;
    delete window.videojs;
    delete window.GLightbox;
    vi.restoreAllMocks();
});

describe('Velocity vendor integrations', () => {
    test('loads required assets once and enhances phone, audio, video, and gallery markup', async () => {
        document.documentElement.dataset.velocityVendorBase = '/assets/velocity/vendor';
        document.documentElement.dataset.velocityAssetVersion = '13';
        document.body.innerHTML = `
            <form>
                <input name="phone" value="100" data-velocity-phone-input data-velocity-phone-full-name="phone_full">
                <input type="hidden" name="phone_full">
            </form>
            <div data-velocity-audio-player><audio controls></audio></div>
            <video data-velocity-video-player controls></video>
            <a href="/photo.jpg" data-velocity-lightbox data-gallery="photos">Photo</a>
            <iframe src="https://example.test/embed"></iframe>
        `;
        const appendSpy = installVendorMocks();

        await enhanceVendorContent(document);
        await enhanceVendorContent(document);
        document.querySelector('form').dispatchEvent(new SubmitEvent('submit'));

        expect(window.intlTelInput).toHaveBeenCalledOnce();
        expect(window.GreenAudioPlayer).toHaveBeenCalledOnce();
        expect(window.GreenAudioPlayer).toHaveBeenCalledWith(
            expect.any(HTMLElement),
            expect.objectContaining({ showDownloadButton: false, stopOthersOnPlay: true }),
        );
        expect(document.querySelector('[data-velocity-audio-player]').hasAttribute('data-velocity-audio-enhanced')).toBe(true);
        expect(window.videojs).toHaveBeenCalledOnce();
        expect(window.GLightbox).toHaveBeenCalledOnce();
        expect(document.querySelector('[name="phone_full"]').value).toBe('+2010100');
        expect(document.querySelector('video').classList.contains('video-js')).toBe(true);
        expect(document.querySelector('iframe').dataset.velocityVideoPlayer).toBeUndefined();
        expect(appendSpy.mock.calls.filter(([node]) => node.tagName === 'SCRIPT')).toHaveLength(4);
    });

    test('retains the native audio fallback when the custom player cannot initialize', async () => {
        document.body.innerHTML = '<div data-velocity-audio-player><audio controls></audio></div>';
        window.GreenAudioPlayer = vi.fn(() => {
            throw new Error('Player initialization failed');
        });

        const enhanced = await enhanceVendorContent(document);
        const wrapper = document.querySelector('[data-velocity-audio-player]');

        expect(enhanced).toBe(0);
        expect(wrapper.hasAttribute('data-velocity-audio-enhanced')).toBe(false);
        expect(wrapper.querySelector('audio').hasAttribute('controls')).toBe(true);
    });

    test('stops every other audio element when a player starts', async () => {
        document.body.innerHTML = `
            <div data-velocity-audio-player><audio controls></audio></div>
            <div data-velocity-audio-player><audio controls></audio></div>
        `;
        window.GreenAudioPlayer = vi.fn();
        const [first, second] = document.querySelectorAll('audio');
        Object.defineProperty(second, 'paused', { configurable: true, value: false });
        const pause = vi.spyOn(second, 'pause').mockImplementation(() => {});

        await enhanceVendorContent(document);
        first.dispatchEvent(new Event('play'));

        expect(pause).toHaveBeenCalledOnce();
    });

    test('shows an unavailable duration cleanly for open-ended audio responses', async () => {
        document.body.innerHTML = '<div data-velocity-audio-player><audio controls></audio></div>';
        window.GreenAudioPlayer = vi.fn(function (wrapper) {
            wrapper.insertAdjacentHTML('beforeend', '<span class="controls__total-time">Infinity:NaN</span>');
        });

        await enhanceVendorContent(document);

        expect(document.querySelector('.controls__total-time').textContent).toBe('--:--');
    });

    test('deduplicates direct asset requests and rejects failed assets without changing content', async () => {
        const append = document.head.append.bind(document.head);
        const appendSpy = vi.spyOn(document.head, 'append').mockImplementation((node) => {
            append(node);
            queueMicrotask(() => node.dispatchEvent(new Event('error')));
            return node;
        });

        const first = loadStyle('test/missing.css');
        const second = loadStyle('test/missing.css');
        expect(first).toBe(second);
        await expect(first).rejects.toThrow('Unable to load test/missing.css');

        const script = loadScript('test/missing.js', 'missingVendor');
        await expect(script).rejects.toThrow('Unable to load test/missing.js');
        expect(appendSpy).toHaveBeenCalledTimes(2);
    });
});
