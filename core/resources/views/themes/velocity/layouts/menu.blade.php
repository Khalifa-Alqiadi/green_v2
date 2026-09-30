@if($velocityMenuLinks->isNotEmpty() || $velocityMenuCtaLink)
        <button
            class="velocity-icon-button velocity-nav-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="velocity-primary-navigation"
            aria-label="{{ __('frontend.quickLinks') }}"
            data-velocity-nav-toggle
        >
            @include(theme_view('components.icon'), ['name' => 'menu'])
        </button>
        <nav class="velocity-nav" id="velocity-primary-navigation" aria-label="{{ __('frontend.quickLinks') }}">
            <div class="velocity-nav__mobile-head">
                <button
                    class="velocity-icon-button velocity-nav__back"
                    type="button"
                    aria-label="{{ __('backend.back') }}"
                    data-velocity-nav-back
                    hidden
                >
                    @include(theme_view('components.icon'), ['name' => 'arrow-left'])
                </button>
                <a class="velocity-nav__mobile-root-brand" href="{{ Helper::homeURL() }}" data-velocity-nav-root-brand>
                    <img
                        src="{{ route('fileView', ['path' => 'settings/'.($velocityLogo ?: 'nologo.png')]) }}"
                        alt="{{ $velocitySiteTitle }}"
                        width="{{ $velocityLogo ? 230 : 172 }}"
                        height="50"
                        loading="eager"
                        decoding="async"
                    >
                </a>
                <div class="velocity-nav__mobile-title" data-velocity-nav-context hidden>
                    <span>{{ Helper::GeneralSiteSettings('site_title_'.Helper::currentLanguage()->code) }}</span>
                    <strong data-velocity-nav-title>{{ __('frontend.quickLinks') }}</strong>
                </div>
                <button
                    class="velocity-icon-button velocity-nav__close"
                    type="button"
                    aria-controls="velocity-primary-navigation"
                    aria-label="{{ __('backend.close') }}"
                    data-velocity-nav-close
                >
                    @include(theme_view('components.icon'), ['name' => 'close'])
                </button>
            </div>
            <div class="velocity-nav__viewport">
                <ul
                    class="velocity-nav__list"
                    data-velocity-menu-panel
                    data-velocity-menu-root
                    data-velocity-menu-label="{{ __('frontend.quickLinks') }}"
                >
                    @include(theme_view('components.menu-items'), [
                        'links' => $velocityMenuLinks,
                        'level' => 1,
                        'path' => 'primary',
                        'WebmasterSection' => $WebmasterSection ?? null,
                    ])
                </ul>
            </div>
            <div class="velocity-nav__mobile-footer">
                @if($velocityMenuCtaLink)
                    <a
                        class="velocity-button velocity-button--primary"
                        href="{{ $velocityMenuCtaLink->url ?: '#' }}"
                        target="{{ $velocityMenuCtaLink->target ?: '_self' }}"
                        @if(($velocityMenuCtaLink->target ?: '_self') === '_blank') rel="noopener" @endif
                    >
                        <span>{{ $velocityMenuCtaLink->title }}</span>
                        <span class="velocity-arrow" aria-hidden="true">→</span>
                    </a>
                @endif
            </div>
        </nav>
        <button
            class="velocity-nav__backdrop"
            type="button"
            aria-controls="velocity-primary-navigation"
            aria-label="{{ __('backend.close') }}"
            data-velocity-nav-close
            hidden
        ></button>
@endif
