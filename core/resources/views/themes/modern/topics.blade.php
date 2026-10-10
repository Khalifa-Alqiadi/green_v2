@extends(theme_view('layouts.master'))

@section('content')
    <div>
        <?php
        $title_var = "title_".@Helper::currentLanguage()->code;
        $title_var2 = "title_".config('smartend.default_language');
        $details_var = "details_".@Helper::currentLanguage()->code;
        $details_var2 = "details_".config('smartend.default_language');
        $slug_var = "seo_url_slug_".@Helper::currentLanguage()->code;
        $slug_var2 = "seo_url_slug_".config('smartend.default_language');

        $webmaster_section_title = "";
        $category_title = "";
        $page_title = "";
        $category_image = "";

        $custom_css_code = @$WebmasterSection->css_code;
        $custom_js_code = @$WebmasterSection->js_code;
        $custom_body_code = @$WebmasterSection->body_code;

        if (@$WebmasterSection != "none") {
            if (@$WebmasterSection->$title_var != "") {
                $webmaster_section_title = @$WebmasterSection->$title_var;
            } else {
                $webmaster_section_title = @$WebmasterSection->$title_var2;
            }
            $page_title = $webmaster_section_title;
            if (@$WebmasterSection->photo != "") {
                $category_image = route("fileView", ["path" => 'topics/'.@$WebmasterSection->photo]);
            }
        }
        if ($CurrentCategory != "none") {
            if (!empty($CurrentCategory)) {
                if (@$CurrentCategory->$title_var != "") {
                    $category_title = @$CurrentCategory->$title_var;
                } else {
                    $category_title = @$CurrentCategory->$title_var2;
                }
                $page_title = $category_title;
                if (@$CurrentCategory->photo != "") {
                    $category_image = route("fileView", ["path" => 'sections/'.@$CurrentCategory->photo]);
                }

                $custom_css_code .= @$CurrentCategory->css_code;
                $custom_js_code .= @$CurrentCategory->js_code;
                $custom_body_code .= @$CurrentCategory->body_code;
            }
        }
        if (!empty(@$DBTag)) {
            $page_title = $DBTag->title;
        }
        $Category_description = null;
        $isNewsSection = (int) (@$WebmasterSection->id) === 3;
        $isMagazineSection = (int) (@$WebmasterSection->id) === 18;
        $isScholarSection = (int) (@$WebmasterSection->id) === 15;
        $isVideoSection = (int) (@$WebmasterSection->type) === 2;
        $isCatalogSection = $isMagazineSection || $isScholarSection || $isVideoSection;
        if (trim(@$CurrentCategory->$details_var) != "") {
            $Category_description = @$CurrentCategory->$details_var;
        }
        if (@$page_type == "tag" && trim(@$TagDescription)) {
            $Category_description = @$TagDescription;
        }
        ?>
        {{-- @if($category_image !="")
            @include(theme_view("topic.cover"))
        @endif --}}
        <section class="breadcrumbs">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>{{  (@$search_word !="")?(__('backend.resultsFoundFor')." [ ".@$search_word." ]"):$page_title }}</h1>
                    <ol>
                        <li><a href="{{ Helper::homeURL() }}">{{ __("backend.home") }}</a></li>
                        @if(@$search_word !="")
                            <li class="active">{!! __("backend.search") !!}</li>
                        @elseif($webmaster_section_title !="")
                            <li class="active"><a
                                    href="{{ Helper::sectionURL(@$WebmasterSection->id) }}">{!! $webmaster_section_title !!}</a>
                            </li>
                        @elseif(@$search_word!="")
                            <li class="active">{{ @$search_word }}</li>
                        @elseif(!empty(@$DBTag))
                            <li class="active">{{ @$DBTag->title }}</li>
                        @else
                            <li class="active">{{ @$User->name }}</li>
                        @endif
                        @if($category_title !="")
                            <li class="active"><a
                                    href="{{ Helper::categoryURL(@$CurrentCategory->id) }}">{{ $category_title }}</a>
                            </li>
                        @endif
                    </ol>
                </div>
                @if($Category_description)
                    <div class="text-muted mt-2 category-details">
                        {!! nl2br($Category_description) !!}
                    </div>
                @endif
            </div>
        </section>
        <section id="content">
            <div class="scholars-ornament2"></div>
            <div class="container">
                <div class="row">
                    @if(@count($Categories)>0 && !$isCatalogSection)
                        @include(theme_view('layouts.side'), ['hideMostViewed' => $isNewsSection])
                    @endif
                    <div
                        class="col-lg-{{(@count($Categories)>0 && !$isCatalogSection)? "9":"12"}} col-md-{{(@count($Categories)>0 && !$isCatalogSection)? "7":"12"}} col-sm-12 col-xs-12">
                        @if($isCatalogSection)
                            <div class="yh-catalog-intro">
                                <span class="yh-catalog-intro__mark" aria-hidden="true"></span>
                                <div>
                                    <h2>{{ $page_title }}</h2>
                                </div>
                                @if($isScholarSection)
                                    <form method="GET" action="{{ Helper::sectionURL($WebmasterSection->id) }}" class="yh-catalog-search">
                                        <label class="visually-hidden" for="scholar-catalog-search">{{ __('frontend.search') }}</label>
                                        <input id="scholar-catalog-search" type="search" name="search_word" value="{{ request('search_word') }}" placeholder="{{ __('frontend.search') }}">
                                        <button type="submit" aria-label="{{ __('frontend.search') }}"><i class="fa-solid fa-magnifying-glass"></i></button>
                                    </form>
                                @endif
                            </div>
                            @if(@count($Categories)>0)
                                <nav class="yh-catalog-categories" aria-label="{{ __('frontend.categories') }}">
                                    @foreach($Categories as $CatalogCategory)
                                        <a href="{{ Helper::categoryURL($CatalogCategory->id, '', $CatalogCategory) }}" class="{{ (int) (@$CurrentCategory->id) === (int) $CatalogCategory->id ? 'is-active' : '' }}">{{ $CatalogCategory->$title_var ?: $CatalogCategory->$title_var2 }}</a>
                                    @endforeach
                                </nav>
                            @endif
                        @endif
                        @if($Topics->total() == 0)
                            <div class="p-5 card text-center no-data">
                                <i class="fa fa-desktop fa-5x opacity-50"></i>
                                <h5 class="mt-3 text-muted">{{ __('frontend.noData') }}</h5>
                            </div>
                        @else
                            <div class="{{ $isNewsSection ? 'yh-news-list' : ($isMagazineSection ? 'yh-issue-grid' : ($isScholarSection ? 'yh-scholar-grid' : ($isVideoSection ? 'yh-video-grid' : 'row'))) }}">
                                @if($Topics->total() > 0)

                                        <?php
                                        $cols_lg = 4;
                                        $cols_md = 6;
                                        if (count($Categories) > 0) {
                                            $cols_lg = 6;
                                            $cols_md = 12;
                                        }
                                        if ((int) (@$WebmasterSection->id) === 15) {
                                            $cols_lg = 6;
                                            $cols_md = 6;
                                        }
                                        ?>
                                    @foreach($Topics as $Topic)
                                            <?php
                                            if ($Topic->$title_var != "") {
                                                $title = $Topic->$title_var;
                                            } else {
                                                $title = $Topic->$title_var2;
                                            }
                                            if ($Topic->$details_var != "") {
                                                $details = $details_var;
                                            } else {
                                                $details = $details_var2;
                                            }

                                            $topic_link_url = Helper::topicURL($Topic->id, "", $Topic);
                                            ?>
                                        @if($isNewsSection)
                                            @include(theme_view('topic.news-list-card'), ['Topic' => $Topic])
                                        @elseif($isMagazineSection)
                                            @include(theme_view('topic.magazine-card'), ['Topic' => $Topic])
                                        @elseif($isScholarSection)
                                            @include(theme_view('topic.scholar-card'), ['Topic' => $Topic])
                                        @elseif($isVideoSection)
                                            @include(theme_view('topic.video-card'), ['Topic' => $Topic])
                                        @else
                                            <div class="col-lg-{{$cols_lg}} col-md-{{$cols_md}}">
                                                @include(theme_view("topic.card"),["Topic"=>$Topic])
                                            </div>
                                        @endif
                                    @endforeach
                            </div>
                            <div id="topics_listing">

                            </div>
                            <div class="row" id="listing_loader" style="display: none">
                                <div class="col col-sm-12">
                                    <div class="p-2 text-center">
                                        <button class="btn btn-light loader-btn">
                                            <span class="loader"></span> {{ __("frontend.viewMore") }}</button>
                                    </div>
                                </div>
                            </div>
                            @push('after-scripts')
                                <script>
                                    $(document).ready(function () {
                                        let page = 1;
                                        let finished = 0;
                                        let in_process = 0;

                                        $(window).off('scroll.infiniteScroll').on('scroll.infiniteScroll', function () {
                                            const viewportBottom = $(window).scrollTop() + $(window).height();

                                            const footerTop = $('#footer').offset().top;

                                            if (viewportBottom >= footerTop - 400) {
                                                if (!in_process && !finished) {
                                                    page++;
                                                    loadMoreData(page);
                                                }
                                            }

                                        });

                                        function loadMoreData(page) {
                                            $.ajax({
                                                url: '{{ route("HomeTopicsList") }}/{{ (@$WebmasterSection->id>0)?@$WebmasterSection->id:0 }}/{{ (@$CurrentCategory->id >0)?@$CurrentCategory->id:0 }}/{{ config('smartend.frontend_pagination') }}' + '?page=' + page + "&tag={{ @$DBTag->id }}&lang={{ @Helper::currentLanguage()->code }}&search_word={{ urlencode(@$search_word) }}",
                                                method: 'GET',
                                                beforeSend: function () {
                                                    in_process = 1;
                                                    $('#listing_loader').show();
                                                },
                                                success: function (data) {
                                                    in_process = 0;
                                                    $('#listing_loader').hide();
                                                    if (data.trim() === "") {
                                                        finished = 1;
                                                        console.log(1);
                                                        return;
                                                    }
                                                    $('#topics_listing').append(data);
                                                },
                                                error: function (xhr) {
                                                    console.log(xhr.responseText);
                                                }
                                            });
                                        }

                                    });
                                </script>
                            @endpush
                        @endif
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include(theme_view('layouts.popup'),['Popup'=>@$Popup])
@endsection
@if((int) (@$WebmasterSection->id) === 3)
    @push('after-styles')
        <style>
            .yh-news-list{display:flex;flex-direction:column;gap:18px}
            .yh-news-list__item{display:grid;grid-template-columns: minmax(180px, 342px) minmax(0, 1fr);overflow:hidden;background:#fff;border:1px solid color-mix(in srgb,var(--brand-secondary) 14%,transparent);border-radius:14px;transition:border-color .2s,transform .2s,box-shadow .2s}
            .yh-news-list__item:hover{border-color:color-mix(in srgb,var(--brand-primary) 45%,transparent);transform:translateY(-2px);box-shadow:0 14px 34px rgba(0,0,0,.06)}
            .yh-news-list__image{display:block;min-height:170px;overflow:hidden;background:color-mix(in srgb,var(--brand-secondary) 8%,#fff)}
            .yh-news-list__image img,.yh-news-list__placeholder{display:block;width:100%;height:100%;min-height:170px;object-fit:cover}
            .yh-news-list__image img{transition:transform .35s}
            .yh-news-list__item:hover .yh-news-list__image img{transform:scale(1.04)}
            .yh-news-list__placeholder{display:grid;place-items:center;color:var(--brand-secondary);font-size:2.5rem}
            .yh-news-list__body{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:20px 24px;min-width:0}
            .yh-news-list__date{color:var(--brand-primary);font-size:.8rem;font-weight:700;margin-bottom:8px}
            .yh-news-list__body h2{font-size:clamp(1.15rem,2vw,1.55rem);line-height:1.45;margin:0 0 8px;font-weight:700}
            .yh-news-list__body h2 a{color:var(--brand-secondary);text-decoration:none}
            .yh-news-list__body h2 a:hover{color:var(--brand-primary)}
            .yh-news-list__body p{color:#62736c;line-height:1.7;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
            .yh-news-list__read{display:inline-flex;align-items:center;gap:9px;color:var(--brand-primary);font-size:.9rem;font-weight:700;text-decoration:none}
            .yh-news-list__read:hover{gap:14px;color:var(--brand-secondary)}
            #topics_listing>.yh-news-list{margin-top:18px}
            @media(max-width:575px){.yh-news-list__item{grid-template-columns:1fr}.yh-news-list__image,.yh-news-list__image img,.yh-news-list__placeholder{height:190px;min-height:190px}.yh-news-list__body{padding:18px}}
        </style>
    @endpush
@endif
@if (@in_array(@$WebmasterSection->type, [3]))
    @push('before-styles')
        <link rel="stylesheet"
              href="{{ theme_asset('vendor/green-audio-player/css/green-audio-player.min.css') }}?v={{ Helper::system_version() }}"/>
    @endpush
    @push('after-scripts')
        <script
            src="{{ theme_asset('vendor/green-audio-player/js/green-audio-player.min.js') }}?v={{ Helper::system_version() }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                GreenAudioPlayer.init({
                    selector: '.audio-player',
                    stopOthersOnPlay: true,
                    showTooltips: true,
                });
            });
        </script>
    @endpush
@endif
@if($custom_css_code !="" || $custom_js_code !="")
    @push('after-styles')
        @if($custom_css_code !="")
            <style>
                {!! $custom_css_code !!}
            </style>
        @endif
        {!! $custom_js_code !!}
    @endpush
@endif
@if($custom_body_code !="")
    @push('before-footer')
        {!! Blade::render($custom_body_code) !!}
    @endpush
@endif
