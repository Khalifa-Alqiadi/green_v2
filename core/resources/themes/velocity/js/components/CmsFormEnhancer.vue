<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    target: {
        type: String,
        required: true,
    },
    feedbackTarget: {
        type: String,
        default: '',
    },
    submittingLabel: {
        type: String,
        default: 'Submitting',
    },
    errorLabel: {
        type: String,
        default: 'Unable to submit the form.',
    },
    closeTarget: {
        type: String,
        default: '',
    },
    successCloseDelay: {
        type: Number,
        default: 0,
    },
});

const announcement = ref('');
let form = null;
let controller = null;
let submitting = false;
let closeTimer = null;

function syncPhoneFallbacks() {
    form.querySelectorAll('[data-velocity-phone-source]').forEach((hiddenInput) => {
        const source = form.elements.namedItem(hiddenInput.dataset.velocityPhoneSource);
        if (! hiddenInput.value && source instanceof HTMLInputElement) {
            hiddenInput.value = source.value;
        }
    });
}

function renderFeedback(status, message) {
    const target = props.feedbackTarget ? document.getElementById(props.feedbackTarget) : null;
    announcement.value = message;
    if (form) {
        form.dataset.velocityFormState = status;
    }

    if (! target) {
        return;
    }

    target.replaceChildren();
    const feedback = document.createElement('p');
    feedback.className = `velocity-form-feedback__message velocity-form-feedback__message--${status}`;
    feedback.setAttribute('role', status === 'success' ? 'status' : 'alert');
    feedback.textContent = message;
    target.append(feedback);
}

async function submit(event) {
    if (submitting) {
        event.preventDefault();
        return;
    }

    if (! form.checkValidity()) {
        return;
    }

    event.preventDefault();
    syncPhoneFallbacks();
    submitting = true;
    form.dataset.velocityFormState = 'submitting';
    form.setAttribute('aria-busy', 'true');
    controller = new AbortController();
    const submitter = event.submitter ?? form.querySelector('[type="submit"]');
    const originalContent = submitter?.innerHTML;

    if (submitter) {
        submitter.disabled = true;
        submitter.textContent = props.submittingLabel;
    }

    try {
        const response = await fetch(form.action, {
            method: (form.method || 'POST').toUpperCase(),
            body: new FormData(form),
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        });
        const payload = await response.json();

        if (! response.ok || ! payload || ! ['success', 'error'].includes(payload.stat)) {
            throw new Error(`Unexpected form response (${response.status}).`);
        }

        renderFeedback(payload.stat, payload.msg || props.errorLabel);
        if (payload.stat === 'success') {
            form.reset();
            window.grecaptcha?.reset?.();

            if (props.closeTarget) {
                if (closeTimer !== null) {
                    window.clearTimeout(closeTimer);
                }
                closeTimer = window.setTimeout(() => {
                    const target = document.getElementById(props.closeTarget);
                    target?.close?.();
                    closeTimer = null;
                }, Math.max(0, props.successCloseDelay));
            }
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            renderFeedback('error', props.errorLabel);
        }
    } finally {
        submitting = false;
        controller = null;
        form.removeAttribute('aria-busy');
        if (submitter) {
            submitter.disabled = false;
            submitter.innerHTML = originalContent;
        }
    }
}

onMounted(() => {
    form = document.getElementById(props.target);
    form?.addEventListener('submit', submit);
});

onBeforeUnmount(() => {
    form?.removeEventListener('submit', submit);
    controller?.abort();
    if (closeTimer !== null) {
        window.clearTimeout(closeTimer);
    }
});
</script>

<template>
    <span class="velocity-visually-hidden" aria-live="polite">{{ announcement }}</span>
</template>
