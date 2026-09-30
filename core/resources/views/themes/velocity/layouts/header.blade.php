@php
    $velocityHeaderMenuId = (int) Helper::GeneralWebmasterSettings('header_menu_id');
    $velocityMenuLinks = $velocityHeaderMenuId > 0
        ? collect(\App\Helpers\SiteMenu::List($velocityHeaderMenuId))->values()
        : collect();
    $velocityMenuCtaLink = $velocityMenuLinks->last();
    $velocityNavigationLinks = $velocityMenuLinks->take(max(0, $velocityMenuLinks->count() - 1))->values();
    $velocitySiteTitle = Helper::GeneralSiteSettings('site_title_'.Helper::currentLanguage()->code);
    $velocityLogo = Helper::GeneralSiteSettings('style_logo_'.Helper::currentLanguage()->code);
@endphp
<header
    id="header"
    class="velocity-header {{ Helper::GeneralSiteSettings('style_header') ? 'velocity-header--sticky' : '' }}"
    data-velocity-sticky-header
>
    <div class="velocity-container--wide velocity-header__shell">
        @include(theme_view('layouts.topbar'))

        <div class="velocity-header__inner">
            <a class="velocity-logo velocity-header__brand" href="{{ Helper::homeURL() }}">
                <span class="velocity-header__brand-media">
                    <img
                        src="{{ route('fileView', ['path' => 'settings/'.($velocityLogo ?: 'nologo.png')]) }}"
                        alt="{{ $velocitySiteTitle }}"
                        width="{{ $velocityLogo ? 230 : 172 }}"
                        height="50"
                        loading="eager"
                        decoding="async"
                    >
                </span>
            </a>

            @include(theme_view('layouts.menu'), [
                'velocityMenuLinks' => $velocityNavigationLinks,
                'velocityMenuCtaLink' => $velocityMenuCtaLink,
            ])

            @if($velocityMenuCtaLink)
                <div class="velocity-header__actions">
                    <a
                        class="velocity-button velocity-button--primary velocity-header__cta"
                        href="{{ $velocityMenuCtaLink->url ?: '#' }}"
                        target="{{ $velocityMenuCtaLink->target ?: '_self' }}"
                        @if(($velocityMenuCtaLink->target ?: '_self') === '_blank') rel="noopener" @endif
                    >
                        <span>{{ $velocityMenuCtaLink->title }}</span>
                        <span class="velocity-arrow" aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</header>
