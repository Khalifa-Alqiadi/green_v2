(function () {
    "use strict";

    const initWorksSliders = () => {
        if (typeof Swiper === "undefined") {
            return;
        }

        const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        document.querySelectorAll(".modern-works-slider").forEach((slider) => {
            if (slider.classList.contains("swiper-initialized")) {
                return;
            }

            const slideCount = slider.querySelectorAll(".swiper-slide").length;
            const controls = slider.querySelector(".modern-works-slider-controls");

            if (slideCount <= 1) {
                slider.classList.add("is-static");
            }

            const swiper = new Swiper(slider, {
                speed: 700,
                slidesPerView: 1,
                spaceBetween: 16,
                watchOverflow: true,
                centerInsufficientSlides: true,
                grabCursor: slideCount > 1,
                loop: slideCount > 2,
                rewind: slideCount > 1 && slideCount <= 2,
                autoplay: slideCount > 1 && !prefersReducedMotion ? {
                    delay: 4500,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                } : false,
                keyboard: {
                    enabled: true,
                    onlyInViewport: true
                },
                navigation: {
                    prevEl: slider.querySelector(".modern-works-slider-prev"),
                    nextEl: slider.querySelector(".modern-works-slider-next")
                },
                pagination: {
                    el: slider.querySelector(".modern-works-slider-pagination"),
                    clickable: true
                },
                breakpoints: {
                    768: {
                        slidesPerView: 2,
                        spaceBetween: 20
                    },
                    1200: {
                        slidesPerView: 2,
                        spaceBetween: 28
                    }
                }
            });

            const syncLockedState = () => {
                slider.classList.toggle("is-locked", swiper.isLocked);
            };

            syncLockedState();
            swiper.on("lock", syncLockedState);
            swiper.on("unlock", syncLockedState);
            swiper.on("breakpoint", syncLockedState);

            if (controls) {
                controls.addEventListener("focusin", () => {
                    if (swiper.autoplay && swiper.autoplay.running) {
                        swiper.autoplay.stop();
                    }
                });

                controls.addEventListener("focusout", () => {
                    if (swiper.autoplay && !prefersReducedMotion) {
                        swiper.autoplay.start();
                    }
                });
            }
        });
    };

    window.addEventListener("load", initWorksSliders);
})();
