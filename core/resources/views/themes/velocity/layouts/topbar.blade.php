@php
    $velocityLanguages = Helper::languagesList();
    $velocityCurrentLanguage = Helper::currentLanguage();
@endphp

<div id="topbar" class="velocity-topbar velocity-header__utility">
    <div class="velocity-topbar__inner">
        <div class="velocity-topbar__controls">
            @if(Helper::GeneralWebmasterSettings('header_search_status'))
                <button
                    class="velocity-control"
                    type="button"
                    data-velocity-dialog-open="velocity-header-search"
                    aria-haspopup="dialog"
                    aria-label="{{ __('backend.search') }}"
                >
                    @include(theme_view('components.icon'), ['name' => 'search'])
                    <span class="velocity-control__label">{{ __('backend.search') }}</span>
                </button>
            @endif

            @if(Helper::GeneralSiteSettings('style_change'))
                <button
                    class="velocity-control"
                    type="button"
                    aria-pressed="false"
                    aria-label="{{ __('backend.themes3') }}"
                    data-velocity-appearance-toggle
                    data-light-label="{{ __('backend.themes1') }}"
                    data-dark-label="{{ __('backend.themes3') }}"
                >
                    @include(theme_view('components.icon'), ['name' => 'moon'])
                    <span class="velocity-control__label" data-velocity-appearance-label>{{ __('backend.themes3') }}</span>
                </button>
            @endif

            @if(Helper::GeneralWebmasterSettings('dashboard_link_status'))
                @auth
                    <details class="velocity-disclosure">
                        <summary class="velocity-control" aria-label="{{ Auth::user()->name }}">
                            @include(theme_view('components.icon'), ['name' => 'user'])
                            <span class="velocity-control__label">{{ Auth::user()->name }}</span>
                        </summary>
                        <div class="velocity-disclosure__panel">
                            <a href="{{ route('adminHome') }}">{{ __('frontend.dashboard') }}</a>
                            @if(in_array(Auth::user()->permissions, [0, 1]))
                                <a href="{{ route('userProfile.show', Auth::user()) }}">{{ __('backend.profile') }}</a>
                            @endif
                            @if(Helper::GeneralWebmasterSettings('inbox_status') && Auth::user()->permissionsGroup?->inbox_status)
                                <a href="{{ route('webmails') }}">{{ __('backend.siteInbox') }}</a>
                            @endif
                            <a href="{{ route('adminLogout') }}">{{ __('backend.logout') }}</a>
                        </div>
                    </details>
                @else
                    <a class="velocity-control" href="{{ route('adminHome') }}" target="_blank" rel="noopener" aria-label="{{ __('frontend.dashboard') }}">
                        @include(theme_view('components.icon'), ['name' => 'user'])
                        <span class="velocity-control__label">{{ __('frontend.dashboard') }}</span>
                    </a>
                @endauth
            @endif

            @if($velocityLanguages->count() > 1)
                <details class="velocity-disclosure">
                    <summary class="velocity-control" aria-label="{{ $velocityCurrentLanguage->title }}">
                        @if($velocityCurrentLanguage->icon)
                            <img
                                class="velocity-language-flag"
                                src="{{ asset('assets/dashboard/images/flags/'.$velocityCurrentLanguage->icon.'.svg') }}"
                                alt=""
                                width="20"
                                height="14"
                                decoding="async"
                            >
                        @else
                            @include(theme_view('components.icon'), ['name' => 'globe'])
                        @endif
                        <span class="velocity-control__label">{{ $velocityCurrentLanguage->title }}</span>
                    </summary>
                    <div class="velocity-disclosure__panel">
                        @foreach($velocityLanguages as $activeLanguage)
                            <a
                                href="{{ ($page_type ?? '') === 'author' ? ($activeLanguage->code === config('smartend.default_language') ? route('authorProfile', ['author' => $page_id]) : route('authorProfileLocalized', ['lang' => $activeLanguage->code, 'author' => $page_id])) : Helper::languageURL($activeLanguage->code, $page_type ?? '', $page_id ?? 0) }}"
                                lang="{{ $activeLanguage->code }}"
                                hreflang="{{ $activeLanguage->code }}"
                            >
                                @if($activeLanguage->icon)
                                    <img
                                        class="velocity-language-flag"
                                        src="{{ asset('assets/dashboard/images/flags/'.$activeLanguage->icon.'.svg') }}"
                                        alt=""
                                        loading="lazy"
                                        width="20"
                                        height="14"
                                        decoding="async"
                                    >
                                @endif
                                {{ $activeLanguage->title }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    </div>
</div>

@if(Helper::GeneralWebmasterSettings('header_search_status'))
    <dialog
        class="velocity-search-dialog"
        id="velocity-header-search"
        aria-labelledby="velocity-search-title"
        aria-describedby="velocity-search-description"
    >
        <div class="velocity-search-dialog__body">
            <div class="velocity-search-dialog__heading">
                <span class="velocity-search-dialog__mark" aria-hidden="true">
                    @include(theme_view('components.icon'), ['name' => 'search'])
                </span>
                <div>
                    <h2 id="velocity-search-title">{{ __('backend.search') }}</h2>
                    <p id="velocity-search-description">{{ __('backend.typeToSearch') }}</p>
                </div>
            </div>
            <form method="GET" action="{{ Helper::sectionURL(1) }}" class="velocity-search-form" role="search">
                <label class="velocity-visually-hidden" for="velocity-header-search-field">{{ __('backend.typeToSearch') }}</label>
                <div class="velocity-search-form__field">
                    <span aria-hidden="true">@include(theme_view('components.icon'), ['name' => 'search'])</span>
                    <input
                        class="velocity-field"
                        id="velocity-header-search-field"
                        type="search"
                        name="search_word"
                        required
                        maxlength="50"
                        autocomplete="off"
                        placeholder="{{ __('backend.typeToSearch') }}"
                        autofocus
                    >
                </div>
                <button class="velocity-button velocity-button--primary" type="submit">{{ __('backend.search') }}</button>
            </form>
            <form method="dialog" class="velocity-search-dialog__close-form">
                <button class="velocity-icon-button velocity-popup__close" type="submit" aria-label="{{ __('backend.close') }}">
                    @include(theme_view('components.icon'), ['name' => 'close'])
                </button>
            </form>
        </div>
    </dialog>
    <noscript>
        <form method="GET" action="{{ Helper::sectionURL(1) }}" class="velocity-container velocity-search-form" role="search">
            <label class="velocity-visually-hidden" for="velocity-header-search-nojs">{{ __('backend.typeToSearch') }}</label>
            <input
                class="velocity-field"
                id="velocity-header-search-nojs"
                type="search"
                name="search_word"
                required
                maxlength="50"
                autocomplete="off"
                placeholder="{{ __('backend.typeToSearch') }}"
            >
            <button class="velocity-button velocity-button--primary" type="submit">{{ __('backend.search') }}</button>
        </form>
    </noscript>
@endif
