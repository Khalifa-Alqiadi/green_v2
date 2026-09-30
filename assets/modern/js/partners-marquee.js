(function () {
    "use strict";

    const initPartnersMarquee = () => {
        if (typeof Swiper === "undefined") {
            return;
        }

        const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        const sliders = document.querySelectorAll(".partners-marquee");

        sliders.forEach((slider) => {
            const slides = slider.querySelectorAll(".swiper-slide");

            if (slider.classList.contains("swiper-initialized")) {
                return;
            }

            if (slides.length < 2 || prefersReducedMotion) {
                slider.classList.add("is-static");
                return;
            }

            const swiper = new Swiper(slider, {
                speed: 6500,
                loop: true,
                allowTouchMove: true,
                slidesPerView: "auto",
                spaceBetween: 24,
                freeMode: {
                    enabled: true,
                    momentum: false
                },
                autoplay: {
                    delay: 0,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                },
                breakpoints: {
                    320: {
                        spaceBetween: 14
                    },
                    768: {
                        spaceBetween: 20
                    },
                    1200: {
                        spaceBetween: 28
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

    window.addEventListener("load", initPartnersMarquee);
})();
