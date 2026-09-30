@php
    $velocityFooterBackground = Helper::GeneralSiteSettings('style_footer_bg');
    $velocityFooterMenu = Helper::GeneralWebmasterSettings('footer_menu_id') > 0
        ? collect(\App\Helpers\SiteMenu::List(Helper::GeneralWebmasterSettings('footer_menu_id')))
        : collect();
    $velocitySiteTitle = Helper::GeneralSiteSettings('site_title_'.Helper::currentLanguage()->code);
    $velocitySiteDescription = Helper::GeneralSiteSettings('site_desc_'.Helper::currentLanguage()->code);
    $velocityCustomFooterLogo = Helper::GeneralSiteSettings('style_footer_logo');
    $velocityFooterLogo = $velocityCustomFooterLogo
        ?: Helper::GeneralSiteSettings('style_logo_'.Helper::currentLanguage()->code);
    $velocityWhatsappAvatar = Helper::GeneralSiteSettings('style_apple') ?: 'nofav.png';
    $velocityContactPageId = (int) Helper::GeneralWebmasterSettings('contact_page_id');
    $velocityContactTopic = $velocityContactPageId > 0 ? Helper::Topic($velocityContactPageId) : null;
    $velocityContactUrl = $velocityContactTopic ? Helper::topicURL($velocityContactTopic->id, '', $velocityContactTopic) : null;
    $velocityContactTitle = $velocityContactTopic
        ? ($velocityContactTopic->{'title_'.Helper::currentLanguage()->code}
            ?: $velocityContactTopic->{'title_'.config('smartend.default_language')})
        : null;
    $velocityContactAddress = Helper::GeneralSiteSettings('contact_t1_'.Helper::currentLanguage()->code);
    $velocityContactPhone = Helper::GeneralSiteSettings('contact_t3');
    $velocityContactEmail = Helper::GeneralSiteSettings('contact_t6');
    $velocityHasContactDetails = filled($velocityContactAddress) || filled($velocityContactPhone) || filled($velocityContactEmail);
@endphp

<footer
    id="footer"
    class="velocity-footer"
    @if($velocityFooterBackground)
        style="--velocity-footer-image: url('{{ route('fileView', ['path' => 'settings/'.$velocityFooterBackground]) }}')"
    @endif
>
    @if($velocityContactUrl || Helper::GeneralSiteSettings('style_footer') == 1)
        <div class="velocity-container--wide velocity-footer__panel">
            @if($velocityContactUrl || $velocityHasContactDetails)
                <section class="velocity-footer-cta" data-velocity-reveal>
                    <div class="velocity-footer-cta__inner">
                        <div class="velocity-footer-cta__content">
                            <p class="velocity-eyebrow">{{ $velocityContactTitle ?: __('frontend.contactDetails') }}</p>
                            <h2 class="velocity-visually-hidden">{{ __('frontend.contactDetails') }}</h2>
                            @if($velocityHasContactDetails)
                                <dl class="velocity-footer-cta__contact">
                                    @if(filled($velocityContactAddress))
                                        <div>
                                            <dt>{{ __('frontend.address') }}</dt>
                                            <dd>{{ $velocityContactAddress }}</dd>
                                        </div>
                                    @endif
                                    @if(filled($velocityContactPhone))
                                        <div>
                                            <dt>{{ __('frontend.callUs') }}</dt>
                                            <dd><a class="velocity-ltr" href="tel:{{ $velocityContactPhone }}">{{ $velocityContactPhone }}</a></dd>
                                        </div>
                                    @endif
                                    @if(filled($velocityContactEmail))
                                        <div>
                                            <dt>{{ __('frontend.email') }}</dt>
                                            <dd><a href="mailto:{{ $velocityContactEmail }}">{{ $velocityContactEmail }}</a></dd>
                                        </div>
                                    @endif
                                </dl>
                            @endif
                        </div>
                        @if($velocityContactUrl)
                            <a class="velocity-button velocity-button--primary" href="{{ $velocityContactUrl }}">
                                <span>{{ $velocityContactTitle }}</span>
                                <span class="velocity-arrow" aria-hidden="true">→</span>
                            </a>
                        @endif
                    </div>
                </section>
            @endif

            @if(Helper::GeneralSiteSettings('style_footer') == 1)
                <div class="velocity-footer__top">
                    <section class="velocity-footer__brand">
                        <a class="velocity-logo velocity-footer__logo {{ $velocityCustomFooterLogo ? 'velocity-footer__logo--custom' : '' }}" href="{{ Helper::homeURL() }}">
                            <img
                                src="{{ route('fileView', ['path' => 'settings/'.($velocityFooterLogo ?: 'nologo.png')]) }}"
                                alt="{{ $velocitySiteTitle }}"
                                width="230"
                                height="50"
                                loading="lazy"
                                decoding="async"
                            >
                        </a>
                        @if(filled($velocitySiteDescription))
                            <p>{{ $velocitySiteDescription }}</p>
                        @endif
                    </section>

                    @if($velocityFooterMenu->isNotEmpty())
                        <div class="velocity-footer__menus">
                            @foreach($velocityFooterMenu->take(4) as $menuLink)
                                <section class="velocity-footer__menu">
                                    <h2 class="velocity-footer__title">{{ $menuLink->title }}</h2>
                                    <ul class="velocity-footer__list">
                                        @if(!empty($menuLink->sub))
                                            @foreach($menuLink->sub as $subLink)
                                                <li><a href="{{ $subLink->url }}" target="{{ $subLink->target }}">{{ $subLink->title }}</a></li>
                                                @if(!empty($subLink->sub))
                                                    @foreach($subLink->sub as $subLink2)
                                                        <li><a href="{{ $subLink2->url }}" target="{{ $subLink2->target }}">{{ $subLink2->title }}</a></li>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        @else
                                            <li><a href="{{ $menuLink->url }}" target="{{ $menuLink->target }}">{{ $menuLink->title }}</a></li>
                                        @endif
                                    </ul>
                                </section>
                            @endforeach
                        </div>
                    @endif

                    @include(theme_view('layouts.subscribe'))

                </div>
            @endif
        </div>
    @endif

    <div class="velocity-footer__bottom">
        <div class="velocity-container velocity-footer__bottom-inner">
            <p class="velocity-footer__meta">
                &copy; {{ now()->year }} {{ __('frontend.AllRightsReserved') }}.
                <a href="{{ Helper::homeURL() }}">{{ $velocitySiteTitle }}</a>
                <span aria-hidden="true"> · </span>
                Powered by <a href="https://smartend.app" target="_blank" rel="noopener">Smartend</a>
            </p>
            @include(theme_view('layouts.social'))
        </div>
    </div>
</footer>

@if(Helper::GeneralSiteSettings('whatsapp_no'))
    @php
        $velocityWhatsapp = preg_replace('/[^0-9]/', '', (string) Helper::GeneralSiteSettings('whatsapp_no'));
    @endphp
    @if($velocityWhatsapp)
        <div class="velocity-whatsapp-widget" data-velocity-whatsapp>
            <section
                class="velocity-whatsapp-card"
                id="velocity-whatsapp-card"
                aria-label="{{ __('frontend.whatsappOpenChat') }}"
                data-velocity-whatsapp-panel
                hidden
            >
                <header class="velocity-whatsapp-card__header">
                    <span class="velocity-whatsapp-card__avatar" aria-hidden="true">
                        <img
                            src="{{ route('fileView', ['path' => 'settings/'.$velocityWhatsappAvatar]) }}"
                            alt=""
                            width="56"
                            height="56"
                            loading="lazy"
                            decoding="async"
                        >
                        <span class="velocity-whatsapp-card__status"></span>
                    </span>
                    <span class="velocity-whatsapp-card__identity">
                        <strong>{{ $velocitySiteTitle }}</strong>
                        <small>{{ __('frontend.whatsappReplyTime') }}</small>
                    </span>
                    <button
                        class="velocity-whatsapp-card__close"
                        type="button"
                        aria-label="{{ __('frontend.whatsappCloseChat') }}"
                        data-velocity-whatsapp-close
                    >
                        @include(theme_view('components.icon'), ['name' => 'close'])
                    </button>
                </header>
                <div class="velocity-whatsapp-card__body">
                    <div class="velocity-whatsapp-card__bubble">
                        <small>{{ $velocitySiteTitle }}</small>
                        <strong>{{ __('frontend.whatsappGreeting') }}</strong>
                        <p>{{ __('frontend.whatsappPrompt') }}</p>
                        <time datetime="{{ now()->format('H:i') }}">{{ now()->format('H:i') }}</time>
                    </div>
                </div>
                <footer class="velocity-whatsapp-card__footer">
                    <a
                        href="https://wa.me/{{ $velocityWhatsapp }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        @include(theme_view('components.icon'), ['name' => 'whatsapp'])
                        <span>{{ __('frontend.whatsappStartChat') }}</span>
                    </a>
                </footer>
            </section>
            <a
                class="velocity-whatsapp-launcher"
                href="https://wa.me/{{ $velocityWhatsapp }}"
                target="_blank"
                rel="noopener noreferrer"
                aria-controls="velocity-whatsapp-card"
                aria-expanded="false"
                aria-label="{{ __('frontend.whatsappOpenChat') }}"
                data-open-label="{{ __('frontend.whatsappOpenChat') }}"
                data-close-label="{{ __('frontend.whatsappCloseChat') }}"
                data-velocity-whatsapp-toggle
            >
                @include(theme_view('components.icon'), ['name' => 'whatsapp'])
            </a>
        </div>
    @endif
@endif

@auth
    @if(!Helper::GeneralSiteSettings('site_status'))
        <div class="velocity-container">
            @component(theme_view('components.status'), ['tone' => 'warning'])
                {{ __('backend.websiteClosedForVisitors') }}
            @endcomponent
        </div>
    @endif
@endauth
