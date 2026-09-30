@php
    $velocityLanguage = Helper::currentLanguage();
    $velocityDefaultAppearance = Helper::GeneralSiteSettings('style_type') ? 'dark' : 'light';
@endphp
<!DOCTYPE html>
<html
    lang="{{ $velocityLanguage->code }}"
    dir="{{ $velocityLanguage->direction }}"
    data-theme="velocity"
    data-appearance="{{ $velocityDefaultAppearance }}"
    data-appearance-locked="true"
>
<head>
    @stack('before-styles')
    @include(theme_view('layouts.head'))
    @include(theme_view('layouts.colors'))
    @yield('headInclude')
    @stack('after-styles')
    @if(Helper::GeneralSiteSettings('css'))
        <style data-velocity-custom-css>{!! Helper::GeneralSiteSettings('css') !!}</style>
    @endif
    {!! Helper::GeneralSiteSettings('js') !!}
</head>
<body class="velocity-page velocity-maintenance dir-{{ $velocityLanguage->direction }}">
    <main class="velocity-maintenance__card velocity-panel" id="main">
        <a class="velocity-logo" href="{{ Helper::homeURL() }}">
            @php
                $velocityLogo = Helper::GeneralSiteSettings('style_logo_'.$velocityLanguage->code);
            @endphp
            <img
                src="{{ route('fileView', ['path' => 'settings/'.($velocityLogo ?: 'nologo.png')]) }}"
                alt="{{ Helper::GeneralSiteSettings('site_title_'.$velocityLanguage->code) }}"
                width="230"
                height="50"
                loading="eager"
                decoding="async"
            >
        </a>
        <div class="velocity-richtext">
            {!! $close_message ?? '' !!}
        </div>
    </main>

    @if(Helper::GeneralSiteSettings('style_preload'))
        <div class="velocity-preloader" data-velocity-preloader role="status">
            <span class="velocity-visually-hidden">{{ __('backend.loading') }}</span>
        </div>
    @endif

    @include(theme_view('layouts.foot'))
    {!! Helper::GeneralSiteSettings('body') !!}
</body>
</html>
