<!DOCTYPE html>
<html class="{{ @Helper::currentLanguage()->code }}" lang="{{ @Helper::currentLanguage()->code }}" dir="{{ @Helper::currentLanguage()->direction }}">

<head>
    <!-- ======= Meta & CSS ======= -->
    @stack('before-styles')
    @include(theme_view('layouts.head'))
    @include(theme_view('layouts.colors'))
    @yield('headInclude')
    @stack('after-styles')
    @if(Helper::GeneralSiteSettings("css")!="")
        <style type="text/css">
            {!! Helper::GeneralSiteSettings("css") !!}
        </style>
    @endif
    {!! Helper::GeneralSiteSettings("js") !!}
</head>

<body class="dir-{{ @Helper::currentLanguage()->direction }} lang-{{ @Helper::currentLanguage()->code }} {{ (!Helper::GeneralSiteSettings("style_change") && Helper::GeneralSiteSettings("style_type"))?"dark":"" }}">

@if(!@$HideHeader)
<!-- ======= Header ======= -->
@include(theme_view('layouts.header'))
@endif

<!-- ======= Main contents ======= -->
<main id="main" class="{{ (Helper::GeneralSiteSettings("style_header"))?"fixed-top-margin":"" }}">
    @if(is_array(session('frontendFormFeedback')))
        <div class="container pt-3" data-frontend-form-feedback>
            <div class="alert {{ session('frontendFormFeedback.status') === 'success' ? 'alert-success' : 'alert-danger' }}"
                 role="alert" aria-live="polite">
                {{ session('frontendFormFeedback.message') }}
            </div>
        </div>
    @endif
    @yield('content')
</main>
<!-- ======= Footer ======= -->
@stack('before-footer')

@if(!@$HideFooter)
@include(theme_view('layouts.footer'))
@endif

@if(Helper::GeneralSiteSettings("style_preload"))
    <div id="preloader"></div>
@endif
<!-- ======= JS Including ======= -->
@stack('before-scripts')
@include(theme_view('layouts.foot'))
@yield('footInclude')
@stack('after-scripts')
{!! Helper::GeneralSiteSettings("body") !!}
@include('components.demo-theme-switcher')
</body>
</html>
