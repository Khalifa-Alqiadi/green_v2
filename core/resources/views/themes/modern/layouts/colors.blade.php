<?php
$style_color1 = Helper::GeneralSiteSettings("style_color1") ?: "#0cbaa4";
$style_color2 = Helper::GeneralSiteSettings("style_color2") ?: "#2e3e4e";
$style_color3 = Helper::GeneralSiteSettings("style_color3") ?: "#f5f8fb";
$style_color4 = Helper::GeneralSiteSettings("style_color4") ?: "#6b7785";
$hero_deep_color = Helper::colorHexToDarken($style_color2, 2.7);
$hero_shadow_color = Helper::colorHexToDarken($style_color2, 3.2);

?>
<style>
    :root {
        --modern-author-accent: {{ $style_color1 }};
        --modern-author-ink: {{ $style_color2 }};
        --modern-author-surface: {{ $style_color3 }};
        --brand-primary: {{ $style_color1 }};
        --brand-secondary: {{ $style_color2 }};
        --brand-surface: {{ $style_color3 }};
        --brand-line: {{ $style_color4 }};
        --brand-primary-dark: {{ Helper::colorHexToDarken($style_color1, 1.25) }};
        --brand-secondary-dark: {{ Helper::colorHexToDarken($style_color2, 1.45) }};
        --scholars-white: #fff;
        --scholars-muted: color-mix(in srgb, {{ $style_color2 }} 56%, #fff);
        --scholars-border: color-mix(in srgb, {{ $style_color4 }} 62%, transparent);
    }

    .topic-read-also {
        --read-also-accent: {{ $style_color1 }};
        --read-also-ink: {{ $style_color2 }};
        --read-also-muted: {{ $style_color4 }};
        --read-also-surface: {{ $style_color3 }};
        --read-also-soft: {{ $style_color3 }};
        --read-also-border: {{ Helper::colorHexToRGB($style_color2, 0.12) }};
        --read-also-accent-soft: {{ Helper::colorHexToRGB($style_color1, 0.11) }};
        --read-also-accent-line: {{ Helper::colorHexToRGB($style_color1, 0.38) }};
        --read-also-pattern: {{ Helper::colorHexToRGB($style_color2, 0.045) }};
        --read-also-shadow: {{ Helper::colorHexToRGB(Helper::colorHexToDarken($style_color2, 1.8), 0.11) }};
    }
    #hero.modern-hero {
        --hero-accent: {{ $style_color1 }};
        --hero-surface: {{ $hero_deep_color }};
        --hero-text: {{ $style_color3 }};
        --hero-on-accent: {{ $style_color3 }};
        --hero-on-light: {{ $style_color2 }};
        --hero-muted: {{ Helper::colorHexToRGB($style_color3, 0.76) }};
        --hero-line: {{ Helper::colorHexToRGB($style_color3, 0.3) }};
        --hero-line-soft: {{ Helper::colorHexToRGB($style_color3, 0.11) }};
        --hero-line-medium: {{ Helper::colorHexToRGB($style_color3, 0.24) }};
        --hero-line-strong: {{ Helper::colorHexToRGB($style_color3, 0.65) }};
        --hero-shade-start: {{ Helper::colorHexToRGB($hero_deep_color, 0.9) }};
        --hero-shade-middle: {{ Helper::colorHexToRGB($hero_deep_color, 0.72) }};
        --hero-shade-soft: {{ Helper::colorHexToRGB($hero_deep_color, 0.2) }};
        --hero-shade-edge: {{ Helper::colorHexToRGB($hero_deep_color, 0.42) }};
        --hero-shade-bottom: {{ Helper::colorHexToRGB($hero_shadow_color, 0.8) }};
        --hero-shade-mobile-start: {{ Helper::colorHexToRGB($hero_deep_color, 0.74) }};
        --hero-shade-mobile-end: {{ Helper::colorHexToRGB($hero_deep_color, 0.32) }};
        --hero-shade-mobile-bottom: {{ Helper::colorHexToRGB($hero_shadow_color, 0.92) }};
        --hero-shade-mobile-soft: {{ Helper::colorHexToRGB($hero_shadow_color, 0.34) }};
        --hero-glass: {{ Helper::colorHexToRGB($hero_deep_color, 0.34) }};
        --hero-glass-soft: {{ Helper::colorHexToRGB($style_color3, 0.1) }};
        --hero-glass-subtle: {{ Helper::colorHexToRGB($style_color3, 0.08) }};
        --hero-cta-border: {{ Helper::colorHexToRGB($style_color3, 0.46) }};
        --hero-text-shadow: {{ Helper::colorHexToRGB($hero_shadow_color, 0.28) }};
        --hero-shadow-soft: {{ Helper::colorHexToRGB($hero_shadow_color, 0.16) }};
        --hero-shadow: {{ Helper::colorHexToRGB($hero_shadow_color, 0.2) }};
        --hero-accent-ring: {{ Helper::colorHexToRGB($style_color1, 0.65) }};
        --hero-accent-glow: {{ Helper::colorHexToRGB($style_color1, 0.24) }};
    }

    .gallery {
        --gallery-accent: {{ $style_color1 }};
        --gallery-surface: {{ $style_color3 }};
        --gallery-frame: #fff;
        --gallery-ink: {{ $style_color2 }};
        --gallery-glow: {{ Helper::colorHexToRGB($style_color1, 0.12) }};
        --gallery-shadow: {{ Helper::colorHexToRGB(Helper::colorHexToDarken($style_color2, 1.8), 0.22) }};
    }

    .modern-works {
        --works-accent: {{ $style_color1 }};
        --works-surface: {{ $style_color3 }};
        --works-card: #fff;
        --works-border: {{ Helper::colorHexToRGB($style_color2, 0.1) }};
        --works-heading: {{ $style_color2 }};
        --works-muted: {{ $style_color4 }};
        --works-kicker-bg: transparent;
        --works-arrow-bg: {{ Helper::colorHexToRGB($style_color3, 0.86) }};
        --works-arrow-color: {{ $style_color2 }};
        --works-shadow: {{ Helper::colorHexToRGB(Helper::colorHexToDarken($style_color2, 1.8), 0.08) }};
    }

    .staff {
        --staff-accent: {{ $style_color1 }};
        --staff-panel: {{ $style_color2 }};
        --staff-surface: #fff;
        --staff-muted: {{ $style_color4 }};
        --staff-text-muted: {{ Helper::colorHexToRGB($style_color2, 0.7) }};
        --staff-heading: {{ $style_color2 }};
        --staff-on-panel: {{ $style_color3 }};
        --staff-overlay: {{ $style_color2 }};
        --staff-overlay-border: {{ Helper::colorHexToRGB($style_color3, 0.22) }};
    }

    .staff .member h3, .staff .member .staff-member-name {
        color: var(--staff-on-panel);
    }

    a:hover, .site-top a, #topbar .contact-info i, #topbar .contact-info a:hover, #topbar .social-links a:hover, .navbar a:hover, .navbar .active, .navbar .active:focus, .navbar li:hover > a, .navbar .dropdown ul a:hover, .navbar .dropdown ul .active:hover, .navbar .dropdown ul li:hover > a, #footer .footer-bottom a, .sidebar-list .list-group .active, .sidebar-list .list-group a:hover, .contact .info i, .owl-stage-outer .item p a, .staff .member .member-info p a {
        color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .navbar .active, .navbar .active:focus {
        color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    #hero .carousel-item::before {
        background-color: {{ Helper::colorHexToRGB(Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),2.4),0.6) }};
    }

    a, #footer .footer-bottom a:hover, .section-title h2, .testimonials .testimonial-item h3, .testimonials .testimonial-author-title {
        color: {{ Helper::GeneralSiteSettings("style_color2") }};
    }

    .read-more-link:hover, .card-title:hover {
        color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    .btn-theme, #footer .footer-newsletter form button:hover, .back-to-top, .section-title h2::before, .section-title h2::after, .contact .info .email:hover i, .contact .info .address:hover i, .contact .info .phone:hover i {
        background: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .btn-primary {
        background: {{ Helper::GeneralSiteSettings("style_color1") }};
        border-color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .btn-primary:hover, .btn-primary:active, .btn-primary:focus {
        background: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
        border-color: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
    }

    .btn-theme:hover, #footer .footer-newsletter form button, .back-to-top:hover {
        background: {{ Helper::GeneralSiteSettings("style_color2") }};
    }



    .fixed-area-menu::-webkit-scrollbar-thumb {
        background-color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .testimonials .testimonial-item .quote-icon-left, .testimonials .testimonial-item .quote-icon-right, .testimonials .testimonial-quote .quote-icon-left, .testimonials .testimonial-quote .quote-icon-right {
        color: {{ Helper::GeneralSiteSettings("style_color4") }};
    }

    .testimonials .testimonial-card {
        border-color: {{ Helper::colorHexToRGB($style_color1,0.12) }};
    }

    .testimonials .testimonial-card:hover {
        border-color: {{ Helper::colorHexToRGB($style_color1,0.28) }};
    }

    .testimonials .testimonial-author-photo-placeholder {
        background: {{ $style_color1 }};
    }

    .testimonials .swiper-pagination .swiper-pagination-bullet {
        border-color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .testimonials .swiper-pagination .swiper-pagination-bullet-active {
        background-color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .btn-secondary, .bg-secondary {
        background-color: {{ Helper::GeneralSiteSettings("style_color1") }}   !important;
        border-color: {{ Helper::GeneralSiteSettings("style_color1") }}   !important;
    }

    .btn-secondary:hover {
        background-color: {{ Helper::GeneralSiteSettings("style_color2") }}   !important;
        border-color: {{ Helper::GeneralSiteSettings("style_color2") }}   !important;
    }

    .tooltip-inner {
        background-color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow::before, .bs-tooltip-top .tooltip-arrow::before {
        border-top-color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .bs-tooltip-auto[data-popper-placement^=bottom] .tooltip-arrow::before, .bs-tooltip-top .tooltip-arrow::before {
        border-bottom-color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .services .icon-box:hover {
        background: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    .services .icon-box {
        border-color: {{ Helper::GeneralSiteSettings("style_color1") }}   !important;
    }

    .services .icon-box .icon{
        background: {{ Helper::GeneralSiteSettings("style_color1") }};
    }
    .services .icon-box:hover .icon i,.services .icon-box h2 {
        color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }
    .services .icon-box:hover h2{
        color: #fff;
    }

    .bottom-article, .widget-title, .contact .info i {
        background: {{ Helper::GeneralSiteSettings("style_color3") }};
        border-color: {{ Helper::GeneralSiteSettings("style_color4") }}  !important;
    }

    .text-primary {
        color: {{ Helper::GeneralSiteSettings("style_color1") }}   !important;
    }

    .bg-primary {
        background-color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
        border-color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    .list-group-item, .card, .form-control, .card {
        border-color: {{ Helper::GeneralSiteSettings("style_color4") }};
    }

    .page-link {
        color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    .active > .page-link, .page-link.active {
        background-color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
        border-color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    #preloader:before {
        border-color: {{ Helper::GeneralSiteSettings("style_color1") }};
        border-top-color: {{ Helper::GeneralSiteSettings("style_color4") }};
    }

    #footer .footer-top .footer-links ul a:hover, #footer a:hover, #footer a:active {
        color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .form-control:focus {
        border-color: {{ Helper::GeneralSiteSettings("style_color4") }};
        box-shadow: 0 0 0 0.25rem{{ Helper::GeneralSiteSettings("style_color3") }};
    }

    .dropdown-item:focus, .dropdown-item:hover {
        color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    .select2-container--default .select2-results__option--selected {
        background-color: {{ Helper::GeneralSiteSettings("style_color3") }};
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: {{ Helper::GeneralSiteSettings("style_color3") }}  !important;
    }

    .bg-light {
        background-color: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
    }

    .text-light {
        color: {{ Helper::GeneralSiteSettings("style_color3") }}  !important;
    }

    .text-light::placeholder {
        color: {{ Helper::GeneralSiteSettings("style_color4") }}  !important;
        opacity: 0.7;
    }

    .text-light::-ms-input-placeholder {
        color: {{ Helper::GeneralSiteSettings("style_color4") }}  !important;
        opacity: 0.7;
    }

    .accordion-item {
        border-color: {{ Helper::GeneralSiteSettings("style_color4") }}  !important;
    }

    .accordion-item:last-of-type .accordion-button.collapsed {
        background: {{ Helper::GeneralSiteSettings("style_color3") }}  !important;
    }

    .accordion-button:not(.collapsed) {
        color: #fff !important;
        background-color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
        box-shadow: none;
    }

    .no-data {
        color: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
    }

    .btn.disabled, .btn:disabled, fieldset:disabled .btn {
        color: {{ Helper::GeneralSiteSettings("style_color4") }}  !important;
    }

    .header-form-search .form-control {
        background-color: {{ Helper::GeneralSiteSettings("style_color3") }}  !important;
    }

    .home-page .testimonials, .home-page .gallery, .home-page .faq, .divider,.staff .staff-showcase {
        border-top: 1px solid {{ Helper::GeneralSiteSettings("style_color3") }}  !important;
    }

    .staff .member span::after {
        background: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    .cookies-accept-box {
        background-color: {{ Helper::colorHexToRGB(Helper::GeneralSiteSettings("style_color2"),0.9) }};
    }

    .post-gallery {
        background: {{ Helper::GeneralSiteSettings("style_color3") }}  !important;
    }

    .line-frame {
        border: 1px solid {{ Helper::GeneralSiteSettings("style_color4") }}  !important;
    }

    .gallery .gallery-card-link {
        border-color: var(--gallery-frame);
    }

    .staff .member {
        background: var(--staff-muted) !important;
    }

    .staff .member .member-info .custom-field-value {
        background: transparent !important;
    }

    .btn-outline-theme {
        border: 1px solid{{ Helper::GeneralSiteSettings("style_color4") }};
        color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .btn-outline-theme:hover, .btn-outline-theme:active {
        background: {{ Helper::GeneralSiteSettings("style_color4") }};
        border: 1px solid{{ Helper::GeneralSiteSettings("style_color4") }};
    }

    #header-search-box {
        background: {{ Helper::GeneralSiteSettings("style_color2") }};
    }

    #header-search-box input[type="search"]::placeholder {
        color: {{ Helper::GeneralSiteSettings("style_color4") }};
    }

    #header-search-box input[type="search"]::-ms-input-placeholder {
        color: {{ Helper::GeneralSiteSettings("style_color4") }};
    }

    #header-search-box .close {
        background: {{ Helper::GeneralSiteSettings("style_color4") }};
        color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .appearance-toggle .checkbox-label .ball {
        background-color: {{ Helper::GeneralSiteSettings("style_color4") }};
    }

    .navbar a, .navbar a:focus {
        color: {{ Helper::GeneralSiteSettings("style_color2") }};
    }

    #footer.modern-footer {
        background: linear-gradient(135deg, {{ $style_color2 }} 0%, {{ Helper::colorHexToDarken($style_color2,1.55) }} 56%, {{ $style_color1 }} 145%);
        background-color: {{ $style_color2 }};
    }

    #footer .modern-footer-contact-icon {
        color: {{ $style_color1 }};
        box-shadow: 0 0 0 6px {{ Helper::colorHexToRGB($style_color1,0.08) }};
    }

    #footer .modern-footer-contact-value:hover, #footer .modern-footer-contact-value:focus, #footer .footer-top .footer-links ul a:hover, #footer .footer-top .footer-links ul a:focus, #footer .footer-bottom a:hover, #footer .footer-bottom a:focus {
        color: {{ $style_color1 }};
    }

    #footer .footer-newsletter form button {
        background: {{ $style_color1 }};
    }

    #footer .footer-newsletter form button:hover, #footer .footer-newsletter form button:focus, #footer .social-links a:hover, #footer .social-links a:focus {
        background: {{ $style_color1 }} !important;
        color: #fff;
    }

    #footer .footer-newsletter form {
        box-shadow: 0 0 0 1px {{ Helper::colorHexToRGB($style_color1,0.08) }};
    }

    .modal-backdrop {
        background-color: {{ Helper::GeneralSiteSettings("style_color2") }};
    }

    /* Dark Mode*/
    .dark, .dark .header-bg, .dark .services .icon-box:hover .icon, .dark .staff, .dark #preloader, .dark .contact .info, .dark .contact .php-email-form {
        background: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }};
        color: #fff;
    }

    .dark .staff {
        --staff-panel: {{ Helper::colorHexToDarken($style_color2, 2.45) }};
        --staff-surface: {{ Helper::colorHexToDarken($style_color2, 1.6) }};
        --staff-heading: {{ $style_color3 }};
        --staff-on-panel: {{ $style_color3 }};
        --staff-text-muted: {{ Helper::colorHexToRGB($style_color3, 0.72) }};
        --staff-overlay: {{ Helper::colorHexToRGB(Helper::colorHexToDarken($style_color2, 2.7), 0.82) }};
        --staff-overlay-border: {{ Helper::colorHexToRGB($style_color3, 0.18) }};
    }

    .dark .gallery {
        --gallery-surface: {{ Helper::colorHexToDarken($style_color2, 1.6) }};
        --gallery-frame: {{ $style_color2 }};
        --gallery-ink: {{ $style_color3 }};
        --gallery-glow: {{ Helper::colorHexToRGB($style_color1, 0.16) }};
        --gallery-shadow: rgba(0, 0, 0, 0.42);
    }

    .dark .modern-works {
        --works-surface: {{ Helper::colorHexToDarken($style_color2, 1.6) }};
        --works-card: {{ Helper::colorHexToDarken($style_color2, 1.25) }};
        --works-border: {{ Helper::colorHexToRGB($style_color3, 0.12) }};
        --works-heading: {{ $style_color3 }};
        --works-muted: {{ Helper::colorHexToRGB($style_color3, 0.58) }};
        --works-kicker-bg: transparent;
        --works-arrow-bg: {{ Helper::colorHexToRGB($style_color3, 0.08) }};
        --works-arrow-color: {{ $style_color3 }};
        --works-shadow: rgba(0, 0, 0, 0.34);
    }

    .dark .topic-read-also {
        --read-also-ink: {{ $style_color3 }};
        --read-also-muted: {{ Helper::colorHexToRGB($style_color3, 0.64) }};
        --read-also-surface: {{ Helper::colorHexToDarken($style_color2, 1.3) }};
        --read-also-soft: {{ Helper::colorHexToDarken($style_color2, 1.6) }};
        --read-also-border: {{ Helper::colorHexToRGB($style_color3, 0.12) }};
        --read-also-accent-soft: {{ Helper::colorHexToRGB($style_color1, 0.16) }};
        --read-also-accent-line: {{ Helper::colorHexToRGB($style_color1, 0.48) }};
        --read-also-pattern: {{ Helper::colorHexToRGB($style_color3, 0.035) }};
        --read-also-shadow: {{ Helper::colorHexToRGB(Helper::colorHexToDarken($style_color2, 2), 0.34) }};
    }

    .dark #preloader:before {
        border-top-color: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }};
    }

    .dark #header.header-scrolled {
        background: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),2.2) }};
        box-shadow: 0 2px 10px rgba(255, 255, 255, .1) !important;
    }

    .dark #topbar.topbar-scrolled {
        background: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }};
    }

   .dark .testimonials .testimonial-item, .dark .testimonials .testimonial-card {
        background-color: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }};
    }

    .dark .testimonials .testimonial-card {
        border-color: {{ Helper::colorHexToRGB($style_color3,0.14) }};
        box-shadow: 0 18px 48px rgba(0, 0, 0, .24);
    }

    .dark .testimonials .testimonial-item .testimonial-img, .dark .testimonials .testimonial-author-photo {
        border-color: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }};
    }

    .dark .appearance-toggle .checkbox-label .ball {
        background-color: {{ Helper::GeneralSiteSettings("style_color2") }};
    }

    .dark .header-scrolled .header-dropdown .btn, .dark .header-scrolled .navbar a {
        color: {{ Helper::GeneralSiteSettings("style_color3") }};
    }

    .dark a, .dark #footer .footer-bottom a:hover, .dark .section-title h2, .dark .staff .member h3, .dark .testimonials .testimonial-item h3, .dark .testimonials .testimonial-author-title {
        color: {{ Helper::GeneralSiteSettings("style_color3") }};
    }

    .dark .testimonials .testimonial-quote {
        color: {{ Helper::GeneralSiteSettings("style_color4") }};
    }

    .dark .testimonials .testimonial-quote .quote-icon-left, .dark .testimonials .testimonial-quote .quote-icon-right {
        color: {{ Helper::colorHexToRGB($style_color3,0.35) }};
    }

    .dark .btn-theme, .dark #footer .footer-newsletter form button {
        background: {{ Helper::GeneralSiteSettings("style_color1") }};
        color: #fff;
        border: 1px solid {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    .dark #footer .footer-newsletter form button:hover {
        background: {{ Helper::GeneralSiteSettings("style_color2") }};
        color: #fff;
    }

    .dark .btn-theme:hover, .dark .back-to-top:hover {
        background: {{ Helper::GeneralSiteSettings("style_color2") }};
        color: {{ Helper::GeneralSiteSettings("style_color1") }};
        border: 1px solid {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    .dark .contact .info h4 {
        color: {{ Helper::GeneralSiteSettings("style_color3") }};
    }

    .dark .contact .info p {
        color: {{ Helper::GeneralSiteSettings("style_color3") }};
    }

    .dark .navbar a, .dark .navbar a:focus, .dark .header-dropdown .btn {
        color: {{ Helper::GeneralSiteSettings("style_color3") }};
    }

    .dark .breadcrumbs, .dark pre, .dark .fixed-area-menu::-webkit-scrollbar-track {
        background-color: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),2.4) }};
    }

    .dark #footer .footer-bottom {
        background: transparent;
    }

    .dark #footer .footer-bottom, .dark .section-border-top {
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .dark .widget-title, .dark .contact .info i {
        background: {{ Helper::GeneralSiteSettings("style_color2") }};
        border-color: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
    }

    .dark .bottom-article {
        background: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }};
        border-color: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
    }

    .dark #footer .copyright, .dark .mobile-nav-toggle.bi-x, .dark #footer .credits {
        color: {{ Helper::GeneralSiteSettings("style_color4") }};
    }

    .dark #footer {
        background-color: {{ Helper::colorHexToDarken($style_color2,2.4) }};
    }

    .dark .form-control, .dark .select2-container--default .select2-selection--multiple, .dark .select2-container--default .select2-selection--single, .dark .select2-dropdown, .dark .select2-search--dropdown .select2-search__field, .dark .iti--inline-dropdown .iti__dropdown-content, .dark .iti__search-input {
        background: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
        border-color: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
        color: #fff !important;
    }

    .dark .select2-container--default .select2-selection--single .select2-selection__rendered, .dark .select2-container--default .select2-search--inline .select2-search__field {
        color: #fff !important;
    }

    .dark .form-control:focus {
        border-color: {{ Helper::GeneralSiteSettings("style_color2") }};
        box-shadow: 0 0 0 0.25rem{{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .dark .select2-container--default .select2-selection--multiple .select2-selection__choice, .dark .select2-container--default .select2-results__option--selected {
        background-color: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }}  !important;
    }

    .dark #footer .footer-newsletter form {
        background: {{ Helper::GeneralSiteSettings("style_color2") }};
        border: 5px solid{{ Helper::GeneralSiteSettings("style_color2") }};
    }

    .dark #topbar {
        box-shadow: 0 0 1px rgba(255, 255, 255, .4);
    }

    .dark .navbar .dropdown ul {
        background-color: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.8) }};
    }

    .dark .navbar .dropdown ul a {
        color: #fff;
    }

    .dark .dropdown-menu.show {
        background-color: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.8) }};
        border-color: rgba(255, 255, 255, .15);
    }

    .dark .accordion-item {
        border-color: {{ Helper::colorHexToRGB(Helper::GeneralSiteSettings("style_color4"),0.2) }}  !important;
        background: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
        color: #fff;
    }

    .dark .accordion-item:last-of-type .accordion-button.collapsed {
        background: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
        color: #fff;
    }

    .dark .accordion-button:not(.collapsed) {
        color: #fff !important;
        background-color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
        box-shadow: none;
    }

    .dark .accordion-button::after {
        filter: brightness(0) invert(1);
    }

    .dark .card, .dark .form-control, .dark .card {
        border-color: {{ Helper::GeneralSiteSettings("style_color2") }};
    }

    .dark .card {
        background: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }}  !important;
        color: {{ Helper::GeneralSiteSettings("style_color3") }};
    }

    .dark .list-group-item {
        background: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }}  !important;
        border-color: {{ Helper::GeneralSiteSettings("style_color2") }};
    }

    .dark .text-muted {
        color: {{ Helper::GeneralSiteSettings("style_color4") }}  !important;
    }

    .dark .home-page .testimonials, .dark .home-page .gallery, .dark .home-page .faq, .dark .divider {
        border-top: 1px solid {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
    }

    .dark .staff .member {
        background-color: var(--staff-muted) !important;
    }

    .dark .gallery .gallery-card-link {
        border-color: var(--gallery-frame);
    }

    .dark .services .icon-box:hover {
        background: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),2.4) }}  !important;
        border-color: {{ Helper::GeneralSiteSettings("style_color1") }}  !important;
    }

    .dark .mobile-nav-toggle {
        color: #fff;
    }

    .dark .navbar-mobile ul {
        background: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }};
    }

    .dark .navbar-mobile > ul > li {
        border-color: {{ Helper::GeneralSiteSettings("style_color2") }}  !important;
    }

    .dark .navbar-mobile .mobile-nav-toggle {
        background: {{ Helper::GeneralSiteSettings("style_color2") }};
        border: 1px solid{{ Helper::GeneralSiteSettings("style_color2") }};
    }

    .dark .page-popup .btn-close, .dark .iti__arrow {
        filter: invert(1);
    }

    @media (max-width: 768px) {
        .dark .navbar-mobile-bg {
            background: {{ Helper::colorHexToDarken(Helper::GeneralSiteSettings("style_color2"),1.6) }};
        }
    }

    .topic-nav-card {
        border: 1px solid{{ Helper::GeneralSiteSettings("style_color4") }};
    }

    .topic-nav-card:hover {
        border-color: {{ Helper::GeneralSiteSettings("style_color1") }};
        color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .topic-nav-icon {
        color: {{ Helper::GeneralSiteSettings("style_color1") }};
    }

    .topic-nav-card:hover .topic-nav-icon {
        background: {{ Helper::GeneralSiteSettings("style_color1") }};
        color: #fff;
    }

    .topic-nav-label {
        color: #6c757d;
    }

    .dark .topic-nav-card {
        border: 1px solid{{ Helper::GeneralSiteSettings("style_color2") }};
    }

    .breadcrumbs, pre {
        background-color: {{ Helper::GeneralSiteSettings("style_color3") }}
    }
    #footer.modern-footer--light .social-links a{
        color: {{ Helper::GeneralSiteSettings("style_color1") }} !important;
    }
    #footer.modern-footer--light .social-links a:hover{
        color: {{ Helper::GeneralSiteSettings("style_color3") }} !important;
    }
</style>
