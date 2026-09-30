(function () {
    "use strict";

    var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var revealSelector = [
        "section",
        ".landing-block",
        ".breadcrumbs",
        ".page-cover",
        ".section-title",
        ".post-card",
        ".member",
        ".gallery-item",
        ".accordion-item",
        ".contact-card",
        ".php-email-form",
        ".custom-form",
        ".sidebar-card",
        ".no-data",
        ".more-btn",
        ".testimonial-card",
        ".partners-marquee .thumbnail",
        "[data-aos]",
        "[data-motion]"
    ].join(",");

    var skipSelector = [
        "#preloader",
        "#hero",
        "#hero *",
        "#heroCarousel",
        "#heroCarousel *",
        ".carousel",
        ".carousel *",
        ".modal:not(.show) *",
        ".owl-stage",
        ".owl-stage *",
        ".swiper-wrapper",
        ".swiper-wrapper *",
        ".video-container",
        ".video-container *",
        ".audio-container",
        ".audio-container *",
        ".audio-player",
        ".audio-player *",
        "iframe",
        "video",
        "audio",
        "[data-motion-static]",
        ".motion-static",
        "[data-motion='none']"
    ].join(",");

    var groupSelector = [
        ".row",
        ".accordion",
        ".listing",
        ".gallery",
        ".services",
        ".staff",
        ".partners-marquee",
        ".testimonials-marquee",
        "#topics_listing"
    ].join(",");

    var staggerSelector = [
        ".post-card",
        ".sidebar-card",
        ".member",
        ".gallery-item",
        ".accordion-item",
        ".contact-card",
        ".testimonial-card",
        ".partners-marquee .thumbnail",
        ".services .icon-box",
        ".landing-block .card"
    ].join(",");

    var observed = new WeakSet();
    var revealed = new WeakSet();
    var observer = null;

    function matches(element, selector) {
        return element.matches && element.matches(selector);
    }

    function closest(element, selector) {
        return element.closest ? element.closest(selector) : null;
    }

    function shouldSkip(element) {
        return !element || !matches(element, revealSelector) || matches(element, skipSelector) || closest(element, skipSelector);
    }

    function isInViewport(element) {
        var rect = element.getBoundingClientRect();
        var height = window.innerHeight || document.documentElement.clientHeight;
        return rect.top < height * .92 && rect.bottom > 0;
    }

    function motionTypeFor(element) {
        var explicit = element.getAttribute("data-motion");
        var aos = element.getAttribute("data-aos");

        if (explicit) {
            return explicit;
        }

        if (aos) {
            if (aos.indexOf("fade") !== -1 && aos.indexOf("up") !== -1) {
                return "fade-up";
            }
            if (aos.indexOf("zoom") !== -1) {
                return "scale-soft";
            }
            return "fade-in";
        }

        if (matches(element, ".page-cover, .breadcrumbs, section")) {
            return "fade-in";
        }

        if (matches(element, ".post-card, .sidebar-card, .gallery-item, .testimonial-card, .partners-marquee .thumbnail, .landing-block .card")) {
            return "scale-soft";
        }

        if (matches(element, ".contact-card, .member")) {
            return "slide-start";
        }

        return "fade-up";
    }

    function explicitDelay(element) {
        var delay = element.getAttribute("data-motion-delay") || element.getAttribute("data-aos-delay");
        var parsed = parseInt(delay, 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
    }

    function staggerDelay(element) {
        if (!matches(element, staggerSelector)) {
            return 0;
        }

        var group = element.getAttribute("data-motion-group");
        var peers;

        if (group) {
            peers = Array.prototype.filter.call(document.querySelectorAll("[data-motion-group]"), function (peer) {
                return peer.getAttribute("data-motion-group") === group;
            });
        } else {
            var parent = closest(element, groupSelector) || element.parentElement;
            peers = parent ? parent.querySelectorAll(staggerSelector) : [];
        }

        var index = Array.prototype.indexOf.call(peers, element);
        return Math.min(Math.max(index, 0) * 70, 420);
    }

    function reveal(element) {
        if (!element || revealed.has(element)) {
            return;
        }

        revealed.add(element);
        element.setAttribute("data-motion-state", "visible");
        element.style.pointerEvents = "auto";

        window.setTimeout(function () {
            if (element.getAttribute("data-motion-state") === "visible") {
                element.setAttribute("data-motion-state", "done");
                element.style.removeProperty("will-change");
            }
        }, 900 + explicitDelay(element) + staggerDelay(element));
    }

    function prepare(element, animateVisible) {
        if (observed.has(element) || shouldSkip(element)) {
            return;
        }

        observed.add(element);

        var motionType = motionTypeFor(element);
        if (motionType === "none") {
            element.setAttribute("data-motion-state", "done");
            return;
        }

        element.setAttribute("data-motion", motionType);

        var delay = explicitDelay(element) || staggerDelay(element);
        if (delay) {
            element.style.setProperty("--motion-delay", delay + "ms");
        }

        if (reduceMotion || (!animateVisible && isInViewport(element)) || !observer) {
            element.setAttribute("data-motion-state", "done");
            return;
        }

        element.setAttribute("data-motion-state", "pending");
        observer.observe(element);
    }

    function scan(root, animateVisible) {
        var scope = root && root.querySelectorAll ? root : document;
        var elements = [];

        if (scope.nodeType === 1 && matches(scope, revealSelector)) {
            elements.push(scope);
        }

        elements = elements.concat(Array.prototype.slice.call(scope.querySelectorAll(revealSelector)));
        elements.forEach(function (element) {
            prepare(element, animateVisible);
        });
    }

    function init() {
        document.documentElement.classList.add("motion-ready");

        if (!reduceMotion && "IntersectionObserver" in window) {
            observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting || entry.intersectionRatio > 0) {
                        observer.unobserve(entry.target);
                        reveal(entry.target);
                    }
                });
            }, {
                root: null,
                rootMargin: "0px 0px -10% 0px",
                threshold: .12
            });
        }

        scan(document);

        if ("MutationObserver" in window) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                        if (node.nodeType === 1) {
                            scan(node, true);
                        }
                    });
                });
            }).observe(document.getElementById("main") || document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
