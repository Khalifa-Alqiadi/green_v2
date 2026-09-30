@php
    $languageCode = Helper::currentLanguage()->code;
    $siteTitle = (string) Helper::GeneralSiteSettings('site_title_'.$languageCode);
    $metaTitle = trim((string) ($PageTitle ?? '')) ?: $siteTitle;
    $metaDescription = trim((string) ($PageDescription ?? ''))
        ?: (string) Helper::GeneralSiteSettings('site_desc_'.$languageCode);
    $metaKeywords = trim((string) ($PageKeywords ?? ''))
        ?: (string) Helper::GeneralSiteSettings('site_keywords_'.$languageCode);
    $favicon = Helper::GeneralSiteSettings('style_fav') ?: 'nofav.png';
    $appleIcon = Helper::GeneralSiteSettings('style_apple') ?: 'nofav.png';
    $velocityTopicImage = trim((string) ($Topic?->photo_file ?? ''));
    $socialImage = $velocityTopicImage !== '' && !in_array(strtolower(basename($velocityTopicImage)), ['default.png', 'noimg.png'], true)
        ? route('fileView', ['path' => 'topics/'.$velocityTopicImage])
        : ($appleIcon !== 'nofav.png' ? route('fileView', ['path' => 'settings/'.$appleIcon]) : null);
    $tagManagerId = trim((string) Helper::GeneralWebmasterSettings('google_tags_id'));
    $velocityPageType = (string) ($page_type ?? '');
    $velocityPageId = (int) ($page_id ?? 0);
    $velocityIsSearchResult = request()->filled('search_word')
        || request()->filled('q')
        || (int) ($WebmasterSection->type ?? -1) === 7;
    $velocityPaginator = ($Topics ?? null) instanceof \Illuminate\Contracts\Pagination\Paginator
        ? $Topics
        : null;
    $velocityPageNumber = $velocityPaginator && !$velocityIsSearchResult
        ? max(1, (int) $velocityPaginator->currentPage())
        : 1;
    $velocityInvalidPagination = $velocityPaginator
        && method_exists($velocityPaginator, 'lastPage')
        && $velocityPaginator->currentPage() > $velocityPaginator->lastPage();
    $velocityRobots = $velocityPageType === '404' || $velocityIsSearchResult || $velocityInvalidPagination
        ? 'noindex,follow'
        : 'index,follow';
    $velocityAppendPage = static function (string $url) use ($velocityPageNumber): string {
        return $velocityPageNumber > 1
            ? $url.(str_contains($url, '?') ? '&' : '?').'page='.$velocityPageNumber
            : $url;
    };
    $canonicalUrl = $velocityAppendPage(Helper::canonicalURL($velocityPageType, $velocityPageId));
    $velocityLanguages = Helper::languagesList();
    $velocityDefaultLanguage = (string) config('smartend.default_language');
    $velocityOgType = $velocityPageType === 'topic' && (int) ($WebmasterSection->type ?? -1) !== 10
        ? 'article'
        : 'website';
    $velocityStructuredData = $velocityPageType === 'home'
        ? [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteTitle,
            'url' => $canonicalUrl,
            'inLanguage' => $languageCode,
        ]
        : null;

    if ($velocityOgType === 'article') {
        $velocityStructuredData = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $metaTitle,
            'description' => $metaDescription,
            'mainEntityOfPage' => $canonicalUrl,
            'url' => $canonicalUrl,
            'inLanguage' => $languageCode,
            'image' => $socialImage,
            'datePublished' => !empty($Topic?->date) ? \Illuminate\Support\Carbon::parse($Topic->date)->toIso8601String() : null,
            'dateModified' => !empty($Topic?->updated_at) ? \Illuminate\Support\Carbon::parse($Topic->updated_at)->toIso8601String() : null,
        ]);
    }
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<meta name="keywords" content="{{ $metaKeywords }}">
<meta name="author" content="{{ $velocityPageType === 'author' ? $metaTitle : url('/') }}">
<meta name="robots" content="{{ $velocityRobots }}">
<meta name="theme-color" content="{{ Helper::GeneralSiteSettings('style_color2') ?: '#102A43' }}">
<meta name="color-scheme" content="light dark">
<link rel="canonical" href="{{ $canonicalUrl }}">
@foreach($velocityLanguages as $velocityAlternateLanguage)
    <link rel="alternate" hreflang="{{ $velocityAlternateLanguage->code }}" href="{{ $velocityAppendPage($velocityPageType === 'author' ? ($velocityAlternateLanguage->code === $velocityDefaultLanguage ? route('authorProfile', ['author' => $velocityPageId]) : route('authorProfileLocalized', ['lang' => $velocityAlternateLanguage->code, 'author' => $velocityPageId])) : Helper::languageURL($velocityAlternateLanguage->code, $velocityPageType, $velocityPageId)) }}">
@endforeach
@if($velocityLanguages->contains('code', $velocityDefaultLanguage))
    <link rel="alternate" hreflang="x-default" href="{{ $velocityAppendPage($velocityPageType === 'author' ? route('authorProfile', ['author' => $velocityPageId]) : Helper::languageURL($velocityDefaultLanguage, $velocityPageType, $velocityPageId)) }}">
@endif
<link rel="icon" href="{{ route('fileView', ['path' => 'settings/'.$favicon]) }}">
<link rel="apple-touch-icon" href="{{ route('fileView', ['path' => 'settings/'.$appleIcon]) }}">

<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
@if($socialImage)
    <meta property="og:image" content="{{ $socialImage }}">
@endif
<meta property="og:site_name" content="{{ $siteTitle }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:type" content="{{ $velocityOgType }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if($socialImage)
    <meta name="twitter:image" content="{{ $socialImage }}">
@endif

@if($velocityStructuredData)
    <script type="application/ld+json">{!! json_encode($velocityStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif

{!! theme_compiled_assets() !!}
<link href="{{ theme_asset('css/authors.css') }}?v={{ Helper::system_version() }}" rel="stylesheet">
<link href="{{ theme_asset('vendor/fontawesome/css/all.min.css') }}?v={{ Helper::system_version() }}" rel="stylesheet">
<link href="{{ theme_asset('vendor/fontawesome/css/v4-shims.min.css') }}?v={{ Helper::system_version() }}" rel="stylesheet">

@if(Helper::GeneralWebmasterSettings('google_tags_status') && preg_match('/^GTM-[A-Z0-9]+$/i', $tagManagerId))
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
        j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@json($tagManagerId));
    </script>
@endif
