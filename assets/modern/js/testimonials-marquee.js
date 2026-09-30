(function () {
    "use strict";

    const initTestimonialsMarquee = () => {
        if (typeof Swiper === "undefined") {
            return;
        }

        const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        const sliders = document.querySelectorAll(".testimonials-marquee");

        sliders.forEach((slider) => {
            const slides = slider.querySelectorAll(".swiper-slide");
            const canLoop = slides.length > 1 && !prefersReducedMotion;
            const reverseDirection = slider.dataset.marqueeDirection === "reverse";

            if (slider.classList.contains("swiper-initialized")) {
                return;
            }

            if (!canLoop) {
                slider.classList.add("is-static");
                return;
            }

            const swiper = new Swiper(slider, {
                speed: 7000,
                loop: true,
                allowTouchMove: true,
                slidesPerView: "auto",
                spaceBetween: 40,
                freeMode: {
                    enabled: true,
                    momentum: false
                },
                autoplay: {
                    delay: 0,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                    reverseDirection: reverseDirection
                },
                breakpoints: {
                    320: {
                        spaceBetween: 20
                    },
                    768: {
                        spaceBetween: 32
                    },
                    1200: {
                        spaceBetween: 44
                    }
                }
            });

            slider.addEventListener("focusin", () => {
                if (swiper.autoplay) {
                    swiper.autoplay.stop();
                }
            });

            slider.addEventListener("focusout", () => {
                if (swiper.autoplay) {
                    swiper.autoplay.start();
                }
            });
        });
    };

    window.addEventListener("load", initTestimonialsMarquee);
})();
