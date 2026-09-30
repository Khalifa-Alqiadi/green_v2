@php
    $velocityLanguage = Helper::currentLanguage();
    $velocityDefaultAppearance = Helper::GeneralSiteSettings('style_type') ? 'dark' : 'light';
    $velocityAppearanceLocked = !Helper::GeneralSiteSettings('style_change');
    $velocityHomePath = trim((string) parse_url(Helper::homeURL(), PHP_URL_PATH), '/');
    $velocityIsHomepage = trim(request()->path(), '/') === $velocityHomePath;
    $velocityIsLandingPage = (int) ($WebmasterSection->type ?? 0) === 10;
@endphp
<!DOCTYPE html>
<html
    class="lang-{{ $velocityLanguage->code }}"
    lang="{{ $velocityLanguage->code }}"
    dir="{{ $velocityLanguage->direction }}"
    data-theme="velocity"
    data-appearance="{{ $velocityDefaultAppearance }}"
    data-appearance-locked="{{ $velocityAppearanceLocked ? 'true' : 'false' }}"
    data-velocity-vendor-base="{{ theme_asset('vendor') }}"
    data-velocity-asset-version="{{ Helper::system_version() }}"
>
<head>
    <script>
        document.documentElement.dataset.js = 'true';
        if (document.documentElement.dataset.appearanceLocked !== 'true') {
            try {
                const storedAppearance = localStorage.getItem('velocity-appearance');
                if (storedAppearance === 'light' || storedAppearance === 'dark') {
                    document.documentElement.dataset.appearance = storedAppearance;
                }
            } catch (error) {}
        }
    </script>
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
<body class="velocity-page {{ $velocityIsHomepage ? 'velocity-page--home' : '' }} {{ $velocityIsLandingPage ? 'velocity-page--landing' : '' }} dir-{{ $velocityLanguage->direction }} lang-{{ $velocityLanguage->code }}" data-velocity-page>
    <a class="velocity-skip-link" href="#main">{{ __('frontend.viewMore') }}</a>

    @if(!($HideHeader ?? false))
        @include(theme_view('layouts.header'))
    @endif

    <main id="main" class="velocity-main {{ Helper::GeneralSiteSettings('style_header') ? 'velocity-main--offset' : '' }}" tabindex="-1" data-velocity-main>
        @if(is_array(session('frontendFormFeedback')))
            <div class="velocity-container" data-frontend-form-feedback>
                @component(theme_view('components.status'), [
                    'tone' => session('frontendFormFeedback.status') === 'success' ? 'success' : 'error',
                    'dismissible' => true,
                ])
                    {{ session('frontendFormFeedback.message') }}
                @endcomponent
            </div>
        @endif

        @php
            $velocityReadiness = \App\Services\ThemeService::compiledReadiness('velocity');
        @endphp
        @if(Auth::check() && !$velocityReadiness['ready'])
            <div class="velocity-container">
                @component(theme_view('components.status'), ['tone' => 'warning'])
                    {{ $velocityReadiness['message'] ?: 'Velocity compiled assets are unavailable; fallback styles are active.' }}
                @endcomponent
            </div>
        @endif

        @yield('content')
    </main>

    @stack('before-footer')
    @if(!($HideFooter ?? false))
        @include(theme_view('layouts.footer'))
    @endif

    @if(Helper::GeneralSiteSettings('style_preload'))
        <div class="velocity-preloader" data-velocity-preloader role="status">
            <span class="velocity-visually-hidden">{{ __('backend.loading') }}</span>
        </div>
    @endif

    @stack('before-scripts')
    @include(theme_view('layouts.foot'))
    @yield('footInclude')
    @stack('after-scripts')
    {!! Helper::GeneralSiteSettings('body') !!}
    @include('components.demo-theme-switcher')
</body>
</html>
