<meta charset="utf-8">
<title>{{(@$PageTitle !="")? @$PageTitle:Helper::GeneralSiteSettings("site_title_" . @Helper::currentLanguage()->code)}}</title>
<meta name="description" content="{{(@$PageDescription !="")? @$PageDescription:Helper::GeneralSiteSettings("site_desc_" . @Helper::currentLanguage()->code)}}"/>
<meta name="keywords" content="{{(@$PageKeywords !="")? @$PageKeywords:Helper::GeneralSiteSettings("site_keywords_" . @Helper::currentLanguage()->code)}}"/>
@php
    $formatSeoDate = static function ($value) {
        if (empty($value)) {
            return null;
        }

        try {
            return $value instanceof \DateTimeInterface
                ? $value->format(\DateTimeInterface::ATOM)
                : \Illuminate\Support\Carbon::parse($value)->toIso8601String();
        } catch (\Throwable $exception) {
            return null;
        }
    };
    $modernMetaAuthors = collect(isset($Topic) ? ($Topic->authors ?? []) : [])->where('status', true)
        ->map(fn ($author) => $author->{'name_'.Helper::currentLanguage()->code} ?: $author->name_ar)
        ->filter()
        ->join(', ');
    $seoType = !empty(@$Topic) ? "article" : "website";
    $seoPublishedTime = !empty(@$Topic) ? $formatSeoDate(@$Topic->created_at ?? null) : null;
    $seoModifiedTime = !empty(@$Topic) ? $formatSeoDate(@$Topic->updated_at ?? null) : null;
@endphp
<meta name="author" content="{{ $modernMetaAuthors ?: ((@$page_type === 'author' && filled($PageTitle ?? null)) ? $PageTitle : URL::to('')) }}"/>

<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

{{-- <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700&display=swap" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700&display=swap"></noscript> --}}
<link href="{{ theme_asset('fonts/fonts-arabic/stylesheet.css') }}?v={{ Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('vendor/fontawesome/css/all.min.css') }}?v={{ Helper::system_version() }}"  rel="stylesheet" media/>
<link href="{{ theme_asset('vendor/fontawesome/css/font-awesome.min.css') }}?v={{ Helper::system_version() }}" rel="stylesheet" media/>
<link href="{{ theme_asset('vendor/animate.css/animate.min.css') }}?v={{ Helper::system_version() }}" rel="stylesheet">
<link href="{{ theme_asset('vendor/bootstrap/css/bootstrap.min.css') }}?v={{ Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('vendor/bootstrap-icons/bootstrap-icons.css') }}?v={{ Helper::system_version() }}" media rel="stylesheet"/>

<link rel="stylesheet" href="{{ theme_asset('vendor/glightbox/css/glightbox.min.css') }}?v={{ Helper::system_version() }}">
<link rel="stylesheet" href="{{ theme_asset('vendor/swiper/swiper-bundle.min.css') }}?v={{ Helper::system_version() }}">

<link rel="stylesheet" href="{{ theme_asset('vendor/owl-carousel/assets/owl.carousel.min.css') }}?v={{ Helper::system_version() }}">
<link rel="stylesheet" href="{{ theme_asset('vendor/owl-carousel/assets/owl.theme.default.min.css') }}?v={{ Helper::system_version() }}">

<link href="{{ theme_asset('css/style.css') }}?v={{ Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/motion.css') }}?v={{ Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/authors.css') }}?v={{ Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/mega-menu.css') }}?v={{ @filemtime(base_path('../assets/modern/css/mega-menu.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/scholars-header-hero.css') }}?v={{ @filemtime(base_path('../assets/modern/css/scholars-header-hero.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-home.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-home.css')) ?: Helper::system_version() }}" rel="stylesheet"/>


@if( @Helper::currentLanguage()->direction=="rtl")
<link href="{{ theme_asset('css/rtl.css') }}?v={{ Helper::system_version() }}" rel="stylesheet"/>
@endif
<link href="{{ theme_asset('css/yemen-news.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-news.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-scholars-directory.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-scholars-directory.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-opinions.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-opinions.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-infographics.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-infographics.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-video-gallery.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-video-gallery.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-books.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-books.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-magazine.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-magazine.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-featured-news-slider.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-featured-news-slider.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-footer.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-footer.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/scholars-compact-header.css') }}?v={{ @filemtime(base_path('../assets/modern/css/scholars-compact-header.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-author-articles.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-author-articles.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-categories.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-categories.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-detail-tuning.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-detail-tuning.css')) ?: Helper::system_version() }}" rel="stylesheet"/>
<link href="{{ theme_asset('css/yemen-videos.css') }}?v={{ @filemtime(base_path('../assets/modern/css/yemen-videos.css')) ?: Helper::system_version() }}" rel="stylesheet"/>

<!-- Favicon and Touch Icons -->
@if(Helper::GeneralSiteSettings("style_fav") !="")
    <link href="{{ route("fileView",["path" =>'settings/'.Helper::GeneralSiteSettings("style_fav") ]) }}" rel="shortcut icon"
          type="image/png">
@else
    <link href="{{ route("fileView",["path" =>'settings/nofav.png' ]) }}" rel="shortcut icon" type="image/png">
@endif
@if(Helper::GeneralSiteSettings("style_apple") !="")
    <link href="{{ route("fileView",["path" =>'settings/'.Helper::GeneralSiteSettings("style_apple") ]) }}" rel="apple-touch-icon">
    <link href="{{ route("fileView",["path" =>'settings/'.Helper::GeneralSiteSettings("style_apple") ]) }}" rel="apple-touch-icon"
          sizes="72x72">
    <link href="{{ route("fileView",["path" =>'settings/'.Helper::GeneralSiteSettings("style_apple") ]) }}" rel="apple-touch-icon"
          sizes="114x114">
    <link href="{{ route("fileView",["path" =>'settings/'.Helper::GeneralSiteSettings("style_apple") ]) }}" rel="apple-touch-icon"
          sizes="144x144">
@else
    <link href="{{ route("fileView",["path" =>'settings/nofav.png' ]) }}" rel="apple-touch-icon">
    <link href="{{ route("fileView",["path" =>'settings/nofav.png' ]) }}" rel="apple-touch-icon" sizes="72x72">
    <link href="{{ route("fileView",["path" =>'settings/nofav.png' ]) }}" rel="apple-touch-icon" sizes="114x114">
    <link href="{{ route("fileView",["path" =>'settings/nofav.png' ]) }}" rel="apple-touch-icon" sizes="144x144">
@endif

<meta property='og:title'
      content='{{@$PageTitle}} {{(@$PageTitle =="")? Helper::GeneralSiteSettings("site_title_" . @Helper::currentLanguage()->code):""}}'/>
@if(@$Topic->photo_file !="")
    <meta property='og:image' content='{{ route("fileView",["path" =>'topics/'.@$Topic->photo_file ]) }}'/>
@elseif(Helper::GeneralSiteSettings("style_apple") !="")
    <meta property='og:image'
          content='{{ route("fileView",["path" =>'settings/'.Helper::GeneralSiteSettings("style_apple") ]) }}'/>
@else
    <meta property='og:image'
          content='{{ route("fileView",["path" =>'settings/nofav.png' ]) }}'/>
@endif
<meta property="og:site_name" content="{{ Helper::GeneralSiteSettings("site_title_" . @Helper::currentLanguage()->code) }}">
<meta property="og:description" content="{{@$PageDescription}}"/>
<meta property="og:url" content="{{ url()->full()  }}"/>
<meta property="og:type" content="{{ @$page_type === 'topic' ? 'article' : 'website' }}"/>

@if($seoType === "article")
    @if($seoPublishedTime)<meta property="article:published_time" content="{{ $seoPublishedTime }}"/>@endif
    @if($seoModifiedTime)<meta property="article:modified_time" content="{{ $seoModifiedTime }}"/>@endif
@endif

<link rel="canonical" href="{{ url()->current() }}">

{{-- Google Tags and google analytics --}}
@if(@Helper::GeneralWebmasterSettings("google_tags_status") && @Helper::GeneralWebmasterSettings("google_tags_id") !="")
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{!! @Helper::GeneralWebmasterSettings("google_tags_id") !!}');</script>
    <!-- End Google Tag Manager -->
@endif
