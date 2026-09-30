<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    target: {
        type: String,
        required: true,
    },
    count: {
        type: Number,
        default: 0,
    },
    autoplay: {
        type: Boolean,
        default: false,
    },
    interval: {
        type: Number,
        default: 6000,
    },
    pagination: {
        type: Boolean,
        default: true,
    },
    previousLabel: {
        type: String,
        default: 'Previous',
    },
    nextLabel: {
        type: String,
        default: 'Next',
    },
});

const activeIndex = ref(0);
const canAutoplay = ref(false);
let track = null;
let slides = [];
let timer = null;
let scrollFrame = null;
let dragPointerId = null;
let dragStartX = 0;
let dragLastX = 0;
let hasDragged = false;

const dragThreshold = 5;

const previousSymbol = computed(() => document.documentElement.dir === 'rtl' ? '→' : '←');
const nextSymbol = computed(() => document.documentElement.dir === 'rtl' ? '←' : '→');

function goTo(index, behavior = 'smooth') {
    if (slides.length === 0) {
        return;
    }

    activeIndex.value = (index + slides.length) % slides.length;
    const slide = slides[activeIndex.value];
    const trackRect = track.getBoundingClientRect();
    const slideRect = slide.getBoundingClientRect();
    const isRtl = document.documentElement.dir === 'rtl';
    const inlineDelta = isRtl
        ? slideRect.right - trackRect.right
        : slideRect.left - trackRect.left;

    track.scrollBy({
        left: inlineDelta,
        behavior,
    });
}

function previous() {
    goTo(activeIndex.value - 1);
}

function next() {
    goTo(activeIndex.value + 1);
}

function syncActiveSlide() {
    if (! track || slides.length === 0) {
        return;
    }

    const trackRect = track.getBoundingClientRect();
    const isRtl = document.documentElement.dir === 'rtl';
    const trackStart = isRtl ? trackRect.right : trackRect.left;
    let closestIndex = 0;
    let closestDistance = Number.POSITIVE_INFINITY;

    slides.forEach((slide, index) => {
        const slideRect = slide.getBoundingClientRect();
        const slideStart = isRtl ? slideRect.right : slideRect.left;
        const distance = Math.abs(slideStart - trackStart);
        if (distance < closestDistance) {
            closestDistance = distance;
            closestIndex = index;
        }
    });

    activeIndex.value = closestIndex;
}

function handleScroll() {
    if (scrollFrame !== null) {
        cancelAnimationFrame(scrollFrame);
    }
    scrollFrame = requestAnimationFrame(syncActiveSlide);
}

function handleKeydown(event) {
    if (event.key === 'ArrowLeft') {
        event.preventDefault();
        document.documentElement.dir === 'rtl' ? next() : previous();
    } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        document.documentElement.dir === 'rtl' ? previous() : next();
    } else if (event.key === 'Home') {
        event.preventDefault();
        goTo(0);
    } else if (event.key === 'End') {
        event.preventDefault();
        goTo(slides.length - 1);
    }
}

function handlePointerDown(event) {
    if (! event.isPrimary || event.pointerType !== 'mouse' || event.button !== 0) {
        return;
    }

    dragPointerId = event.pointerId;
    dragStartX = event.clientX;
    dragLastX = event.clientX;
    hasDragged = false;
    stopTimer();
    track?.setPointerCapture?.(event.pointerId);
}

function handlePointerMove(event) {
    if (event.pointerId !== dragPointerId) {
        return;
    }

    const distance = event.clientX - dragStartX;
    if (! hasDragged && Math.abs(distance) < dragThreshold) {
        return;
    }

    hasDragged = true;
    track?.classList.add('is-dragging');
    event.preventDefault();
    track?.scrollBy({
        left: dragLastX - event.clientX,
        behavior: 'auto',
    });
    dragLastX = event.clientX;
}

function finishDrag(event) {
    if (event.pointerId !== dragPointerId) {
        return;
    }

    track?.releasePointerCapture?.(event.pointerId);
    track?.classList.remove('is-dragging');
    dragPointerId = null;

    if (hasDragged) {
        syncActiveSlide();
        goTo(activeIndex.value);
    }

    startTimer();
}

function handleClick(event) {
    if (! hasDragged) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    hasDragged = false;
}

function handleDragStart(event) {
    event.preventDefault();
}

function handlePointerLeave() {
    if (dragPointerId === null) {
        startTimer();
    }
}

function stopTimer() {
    if (timer !== null) {
        window.clearInterval(timer);
        timer = null;
    }
}

function startTimer() {
    stopTimer();
    if (props.autoplay && canAutoplay.value && props.count > 1) {
        timer = window.setInterval(next, Math.max(props.interval, 3000));
    }
}

function handleVisibility() {
    if (document.hidden) {
        stopTimer();
    } else {
        startTimer();
    }
}

onMounted(() => {
    track = document.getElementById(props.target);
    slides = track ? Array.from(track.querySelectorAll('[data-velocity-carousel-slide]')) : [];
    canAutoplay.value = ! window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    track?.addEventListener('scroll', handleScroll, { passive: true });
    track?.addEventListener('keydown', handleKeydown);
    track?.addEventListener('pointerdown', handlePointerDown);
    track?.addEventListener('pointermove', handlePointerMove);
    track?.addEventListener('pointerup', finishDrag);
    track?.addEventListener('pointercancel', finishDrag);
    track?.addEventListener('click', handleClick, true);
    track?.addEventListener('dragstart', handleDragStart);
    if (track && ! track.hasAttribute('tabindex')) {
        track.setAttribute('tabindex', '0');
    }
    track?.addEventListener('pointerenter', stopTimer);
    track?.addEventListener('pointerleave', handlePointerLeave);
    track?.addEventListener('focusin', stopTimer);
    track?.addEventListener('focusout', startTimer);
    document.addEventListener('visibilitychange', handleVisibility);
    startTimer();
});

onBeforeUnmount(() => {
    stopTimer();
    if (scrollFrame !== null) {
        cancelAnimationFrame(scrollFrame);
    }
    track?.removeEventListener('scroll', handleScroll);
    track?.removeEventListener('keydown', handleKeydown);
    track?.removeEventListener('pointerdown', handlePointerDown);
    track?.removeEventListener('pointermove', handlePointerMove);
    track?.removeEventListener('pointerup', finishDrag);
    track?.removeEventListener('pointercancel', finishDrag);
    track?.removeEventListener('click', handleClick, true);
    track?.removeEventListener('dragstart', handleDragStart);
    track?.removeEventListener('pointerenter', stopTimer);
    track?.removeEventListener('pointerleave', handlePointerLeave);
    track?.removeEventListener('focusin', stopTimer);
    track?.removeEventListener('focusout', startTimer);
    document.removeEventListener('visibilitychange', handleVisibility);
});
</script>

<template>
    <div class="velocity-carousel__controls" data-velocity-island-ready>
        <button class="velocity-icon-button" type="button" :aria-label="previousLabel" @click="previous">
            <span aria-hidden="true">{{ previousSymbol }}</span>
        </button>
        <div v-if="pagination" class="velocity-carousel__pagination" role="group">
            <button
                v-for="slideIndex in count"
                :key="slideIndex"
                class="velocity-carousel__pagination-button"
                type="button"
                :aria-label="`${slideIndex} / ${count}`"
                :aria-current="activeIndex === slideIndex - 1 ? 'true' : 'false'"
                @click="goTo(slideIndex - 1)"
            ></button>
        </div>
        <button class="velocity-icon-button" type="button" :aria-label="nextLabel" @click="next">
            <span aria-hidden="true">{{ nextSymbol }}</span>
        </button>
    </div>
</template>
