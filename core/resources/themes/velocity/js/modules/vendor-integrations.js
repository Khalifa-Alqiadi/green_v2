const loadedStyles = new Map();
const loadedScripts = new Map();
const phoneInstances = new WeakMap();
const audioPlayers = new WeakSet();
const coordinatedAudio = new WeakSet();
const videoPlayers = new WeakMap();
let lightbox = null;

function assetUrl(path) {
    const shell = document.documentElement;
    const base = (shell.dataset.velocityVendorBase || '/assets/velocity/vendor').replace(/\/$/, '');
    const version = shell.dataset.velocityAssetVersion;
    return `${base}/${path}${version ? `?v=${encodeURIComponent(version)}` : ''}`;
}

export function loadStyle(path) {
    if (loadedStyles.has(path)) {
        return loadedStyles.get(path);
    }

    const existing = document.querySelector(`link[data-velocity-vendor-style="${path}"]`);
    if (existing) {
        return Promise.resolve(existing);
    }

    const promise = new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = assetUrl(path);
        link.dataset.velocityVendorStyle = path;
        link.addEventListener('load', () => resolve(link), { once: true });
        link.addEventListener('error', () => reject(new Error(`Unable to load ${path}`)), { once: true });
        document.head.append(link);
    }).catch((error) => {
        loadedStyles.delete(path);
        throw error;
    });

    loadedStyles.set(path, promise);
    return promise;
}

export function loadScript(path, globalName) {
    if (globalName && window[globalName]) {
        return Promise.resolve(window[globalName]);
    }
    if (loadedScripts.has(path)) {
        return loadedScripts.get(path);
    }

    const promise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = assetUrl(path);
        script.defer = true;
        script.dataset.velocityVendorScript = path;
        script.addEventListener('load', () => resolve(globalName ? window[globalName] : script), { once: true });
        script.addEventListener('error', () => reject(new Error(`Unable to load ${path}`)), { once: true });
        document.head.append(script);
    }).catch((error) => {
        loadedScripts.delete(path);
        throw error;
    });

    loadedScripts.set(path, promise);
    return promise;
}

function hiddenPhoneInput(input) {
    const name = input.dataset.velocityPhoneFullName || `${input.name}_full`;
    return input.form?.elements.namedItem(name) ?? null;
}

function syncPhone(input, instance) {
    const hidden = hiddenPhoneInput(input);
    if (! (hidden instanceof HTMLInputElement)) {
        return;
    }

    const international = instance?.getNumber?.();
    hidden.value = international || input.value;
}

async function enhancePhones(root) {
    const inputs = Array.from(root.querySelectorAll('[data-velocity-phone-input]'))
        .filter((input) => ! phoneInstances.has(input));
    if (inputs.length === 0) {
        return 0;
    }

    await Promise.all([
        loadStyle('intl-tel-input/css/intlTelInput.min.css'),
        loadScript('intl-tel-input/js/intlTelInputWithUtils.min.js', 'intlTelInput'),
    ]);

    inputs.forEach((input) => {
        const initialValue = hiddenPhoneInput(input)?.value || input.value;
        const instance = window.intlTelInput(input, {
            countrySearch: true,
            initialCountry: '',
            nationalMode: false,
            separateDialCode: true,
        });
        phoneInstances.set(input, instance);
        if (initialValue) {
            instance.setNumber(initialValue);
        }
        input.form?.addEventListener('submit', () => syncPhone(input, instance), { capture: true });
        input.form?.addEventListener('reset', () => {
            window.setTimeout(() => {
                instance.setNumber(input.defaultValue || '');
                syncPhone(input, instance);
            });
        });
    });

    return inputs.length;
}

function localizeAudioPlayer(wrapper) {
    const play = wrapper.querySelector('.play-pause-btn');
    const volume = wrapper.querySelector('.volume__button');
    const audio = wrapper.querySelector('audio');
    const totalTime = wrapper.querySelector('.controls__total-time');
    const syncPlaybackLabel = () => {
        const label = audio?.paused ? wrapper.dataset.playLabel : wrapper.dataset.pauseLabel;
        if (play && label) {
            play.setAttribute('aria-label', label);
            play.setAttribute('title', label);
        }
    };
    const syncDuration = () => {
        if (totalTime && audio && ! Number.isFinite(audio.duration)) {
            totalTime.textContent = '--:--';
        }
    };
    syncPlaybackLabel();
    syncDuration();
    audio?.addEventListener('play', () => queueMicrotask(syncPlaybackLabel));
    audio?.addEventListener('pause', () => queueMicrotask(syncPlaybackLabel));
    audio?.addEventListener('durationchange', syncDuration);
    audio?.addEventListener('loadedmetadata', syncDuration);
    if (volume && wrapper.dataset.volumeLabel) volume.setAttribute('aria-label', wrapper.dataset.volumeLabel);
}

function coordinateAudioPlayback(wrappers) {
    wrappers.forEach((wrapper) => {
        const audio = wrapper.querySelector('audio');
        if (! audio || coordinatedAudio.has(audio)) {
            return;
        }

        audio.addEventListener('play', () => {
            document.querySelectorAll('audio').forEach((other) => {
                if (other !== audio && ! other.paused) {
                    other.pause();
                }
            });
        });
        coordinatedAudio.add(audio);
    });
}

async function enhanceAudio(root) {
    const wrappers = Array.from(root.querySelectorAll('[data-velocity-audio-player]'))
        .filter((wrapper) => ! audioPlayers.has(wrapper));
    if (wrappers.length === 0) {
        return 0;
    }

    coordinateAudioPlayback(wrappers);

    await Promise.all([
        loadStyle('green-audio-player/css/green-audio-player.min.css'),
        loadScript('green-audio-player/js/green-audio-player.min.js', 'GreenAudioPlayer'),
    ]);

    let enhanced = 0;
    wrappers.forEach((wrapper) => {
        try {
            new window.GreenAudioPlayer(wrapper, {
                enableKeystrokes: true,
                showDownloadButton: false,
                showTooltips: true,
                stopOthersOnPlay: true,
            });
            wrapper.dataset.velocityAudioEnhanced = '';
            audioPlayers.add(wrapper);
            localizeAudioPlayer(wrapper);
            enhanced += 1;
        } catch {
            // Keep the native audio control available when vendor initialization fails.
        }
    });

    return enhanced;
}

async function enhanceVideo(root) {
    const videos = Array.from(root.querySelectorAll('video[data-velocity-video-player]'))
        .filter((video) => ! videoPlayers.has(video));
    if (videos.length === 0) {
        return 0;
    }

    await Promise.all([
        loadStyle('video-js/css/video-js.min.css'),
        loadScript('video-js/js/video.min.js', 'videojs'),
    ]);

    videos.forEach((video) => {
        video.classList.add('video-js', 'vjs-big-play-centered');
        const player = window.videojs(video, { fluid: true, responsive: true });
        videoPlayers.set(video, player);
    });

    return videos.length;
}

async function enhanceLightbox(root) {
    if (! root.querySelector('[data-velocity-lightbox]')) {
        return 0;
    }

    await Promise.all([
        loadStyle('glightbox/css/glightbox.min.css'),
        loadScript('glightbox/js/glightbox.min.js', 'GLightbox'),
    ]);

    if (lightbox) {
        lightbox.reload();
    } else {
        lightbox = window.GLightbox({
            selector: '[data-velocity-lightbox]',
            touchNavigation: true,
            loop: true,
        });
    }

    return root.querySelectorAll('[data-velocity-lightbox]').length;
}

export async function enhanceVendorContent(root = document) {
    const enhancements = await Promise.allSettled([
        enhancePhones(root),
        enhanceAudio(root),
        enhanceVideo(root),
        enhanceLightbox(root),
    ]);

    return enhancements.reduce((total, result) => (
        total + (result.status === 'fulfilled' ? result.value : 0)
    ), 0);
}
