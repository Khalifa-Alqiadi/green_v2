<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    target: {
        type: String,
        required: true,
    },
    paginationTarget: {
        type: String,
        default: '',
    },
    endpoint: {
        type: String,
        required: true,
    },
    page: {
        type: Number,
        default: 1,
    },
    lastPage: {
        type: Number,
        default: 1,
    },
    loadLabel: {
        type: String,
        default: 'View more',
    },
    loadingLabel: {
        type: String,
        default: 'Loading',
    },
    errorLabel: {
        type: String,
        default: 'Unable to load more results.',
    },
    resultsLabel: {
        type: String,
        default: 'results loaded',
    },
});

const currentPage = ref(props.page);
const loading = ref(false);
const error = ref('');
const announcement = ref('');
let controller = null;

const finished = computed(() => currentPage.value >= props.lastPage);

function pageUrl(page) {
    const url = new URL(props.endpoint, window.location.origin);
    url.searchParams.set('page', String(page));

    return url;
}

function responseCards(html) {
    const template = document.createElement('template');
    template.innerHTML = html;

    return Array.from(template.content.querySelectorAll('[data-velocity-topic-card]'));
}

async function loadMore() {
    if (loading.value || finished.value) {
        return;
    }

    const target = document.getElementById(props.target);
    if (! target) {
        error.value = props.errorLabel;
        return;
    }

    loading.value = true;
    error.value = '';
    controller = new AbortController();
    const nextPage = currentPage.value + 1;

    try {
        const response = await fetch(pageUrl(nextPage), {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        });

        if (! response.ok) {
            throw new Error(`Velocity topic request failed with status ${response.status}.`);
        }

        const cards = responseCards(await response.text());
        if (cards.length === 0) {
            currentPage.value = props.lastPage;
            return;
        }

        cards.forEach((card) => target.append(card));
        currentPage.value = nextPage;
        announcement.value = `${cards.length} ${props.resultsLabel}`;

        const firstCard = cards[0];
        firstCard.setAttribute('tabindex', '-1');
        firstCard.focus({ preventScroll: true });
        document.dispatchEvent(new CustomEvent('velocity:content-appended', {
            detail: { root: target, items: cards },
        }));
    } catch (requestError) {
        if (requestError.name !== 'AbortError') {
            error.value = props.errorLabel;
        }
    } finally {
        loading.value = false;
        controller = null;
    }
}

onBeforeUnmount(() => controller?.abort());
</script>

<template>
    <div class="velocity-topic-explorer__controls">
        <button
            v-if="!finished"
            class="velocity-button velocity-button--secondary"
            type="button"
            :disabled="loading"
            @click="loadMore"
        >
            {{ loading ? loadingLabel : loadLabel }}
        </button>
        <p v-if="error" class="velocity-topic-explorer__error" role="alert">
            {{ error }}
        </p>
        <p class="velocity-visually-hidden" aria-live="polite">{{ announcement }}</p>
    </div>
</template>
