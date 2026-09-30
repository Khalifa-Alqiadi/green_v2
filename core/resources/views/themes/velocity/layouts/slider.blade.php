@if(($BannersSettingsId ?? 0) > 0)
    @php
        $velocityBanners = Helper::BannersList($BannersSettingsId);
        $velocityFirstBanner = $velocityBanners->first();
        $velocityBannerType = (int) ($velocityFirstBanner->webmasterBanner->type ?? 0);
        $velocityCode = @Helper::currentLanguage()->code;
        $velocityDefaultCode = config('smartend.default_language');
        $velocityCarouselId = 'velocity-hero-'.$BannersSettingsId;
    @endphp
    @if($velocityBanners->isNotEmpty())
        <section id="hero" class="velocity-hero" aria-label="{{ __('backend.home') }}">
            @if($velocityBannerType === 0)
                <div class="velocity-container velocity-hero__text" data-velocity-reveal>
                    @foreach($velocityBanners as $velocityBanner)
                        @php
                            $velocityDetails = $velocityBanner->{'details_'.$velocityCode}
                                ?: $velocityBanner->{'details_'.$velocityDefaultCode};
                        @endphp
                        @if(filled($velocityDetails))
                            <div class="velocity-rich-content">{!! $velocityDetails !!}</div>
                        @endif
                    @endforeach
                </div>
            @elseif($velocityBannerType === 1)
                <div class="velocity-hero__frame">
                    <div
                        id="{{ $velocityCarouselId }}"
                        class="velocity-carousel velocity-carousel--hero"
                        data-velocity-carousel-track
                        aria-live="polite"
                    >
                        @foreach($velocityBanners as $velocityBanner)
                            @php
                                $velocityTitle = $velocityBanner->{'title_'.$velocityCode}
                                    ?: $velocityBanner->{'title_'.$velocityDefaultCode};
                                $velocityDetails = $velocityBanner->{'details_'.$velocityCode}
                                    ?: $velocityBanner->{'details_'.$velocityDefaultCode};
                                $velocityFile = $velocityBanner->{'file_'.$velocityCode}
                                    ?: $velocityBanner->{'file_'.$velocityDefaultCode};
                                $velocityLink = $velocityBanner->{'link_'.$velocityCode}
                                    ?: ($velocityBanner->{'link_'.$velocityDefaultCode} ?? '');
                            @endphp
                            <article
                                class="velocity-hero__slide"
                                data-velocity-carousel-slide
                                aria-label="{{ $loop->iteration }} / {{ $velocityBanners->count() }}"
                            >
                                @if(filled($velocityFile))
                                    @php
                                        $velocityImageUrl = route('fileView', ['path' => 'banners/'.$velocityFile]);
                                    @endphp
                                    <img
                                        class="velocity-hero__image"
                                        src="{{ $velocityImageUrl }}?w=1920&h=1080&r=fit"
                                        srcset="{{ $velocityImageUrl }}?w=640&h=720&r=fit 640w, {{ $velocityImageUrl }}?w=1280&h=720&r=fit 1280w, {{ $velocityImageUrl }}?w=1920&h=1080&r=fit 1920w"
                                        sizes="100vw"
                                        alt=""
                                        width="1920"
                                        height="1080"
                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                        decoding="async"
                                        @if($loop->first) fetchpriority="high" @endif
                                    >
                                @endif
                                <div class="velocity-hero__shade" aria-hidden="true"></div>
                                @if(filled($velocityTitle) || filled($velocityDetails) || filled($velocityLink))
                                    <div class="velocity-container velocity-hero__content">
                                        @if(filled($velocityTitle))
                                            @if($loop->first)
                                                <h1 class="velocity-hero__title velocity-balanced">{{ $velocityTitle }}</h1>
                                            @else
                                                <h2 class="velocity-hero__title velocity-balanced">{{ $velocityTitle }}</h2>
                                            @endif
                                        @endif
                                        @if(filled($velocityDetails))
                                            <div class="velocity-hero__summary">{!! $velocityDetails !!}</div>
                                        @endif
                                        @if(filled($velocityLink))
                                            <a class="velocity-button velocity-button--primary" href="{{ $velocityLink }}">
                                                {{ __('frontend.moreDetails') }} <span class="velocity-arrow" aria-hidden="true">→</span>
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    @include(theme_view('components.carousel-controls'), [
                        'target' => $velocityCarouselId,
                        'count' => $velocityBanners->count(),
                        'autoplay' => true,
                        'interval' => 6500,
                        'pagination' => true,
                    ])
                </div>
            @else
                <div class="velocity-container velocity-hero__videos">
                    @foreach($velocityBanners as $velocityBanner)
                        @php
                            $velocityTitle = $velocityBanner->{'title_'.$velocityCode}
                                ?: $velocityBanner->{'title_'.$velocityDefaultCode};
                            $velocityDetails = $velocityBanner->{'details_'.$velocityCode}
                                ?: $velocityBanner->{'details_'.$velocityDefaultCode};
                            $velocityFile = $velocityBanner->{'file_'.$velocityCode}
                                ?: $velocityBanner->{'file_'.$velocityDefaultCode};
                            $velocityYoutubeId = $velocityBanner->video_type == 1
                                ? Helper::Get_youtube_video_id($velocityBanner->youtube_link)
                                : null;
                            $velocityVimeoId = $velocityBanner->video_type == 2
                                ? Helper::Get_vimeo_video_id($velocityBanner->youtube_link)
                                : null;
                        @endphp
                        <article class="velocity-video-card velocity-panel">
                            @if($velocityYoutubeId)
                                <iframe
                                    src="https://www.youtube-nocookie.com/embed/{{ $velocityYoutubeId }}"
                                    title="{{ filled($velocityTitle) ? $velocityTitle : __('backend.viewDetails') }}"
                                    loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                    allow="accelerometer; autoplay; encrypted-media; picture-in-picture"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>
                            @elseif($velocityVimeoId)
                                <iframe
                                    src="https://player.vimeo.com/video/{{ $velocityVimeoId }}?title=0&amp;byline=0"
                                    title="{{ filled($velocityTitle) ? $velocityTitle : __('backend.viewDetails') }}"
                                    loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                    allow="autoplay; fullscreen; picture-in-picture"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>
                            @elseif($velocityBanner->video_type == 0 && filled($velocityFile))
                                <video controls playsinline preload="none" data-velocity-video-player>
                                    <source src="{{ route('fileView', ['path' => 'banners/'.$velocityFile]) }}" type="video/mp4">
                                </video>
                            @endif
                            @if(filled($velocityDetails))
                                <div class="velocity-rich-content velocity-video-card__caption">{!! $velocityDetails !!}</div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
@endif
