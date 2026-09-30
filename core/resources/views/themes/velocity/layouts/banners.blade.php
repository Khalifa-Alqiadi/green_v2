@if(($BannersSettingsId ?? 0) > 0)
    @php
        $velocityBanners = Helper::BannersList($BannersSettingsId);
        $velocityBannerType = (int) (optional(optional($velocityBanners->first())->webmasterBanner)->type ?? 0);
        $velocityCode = @Helper::currentLanguage()->code;
        $velocityDefaultCode = config('smartend.default_language');
    @endphp
    @if($velocityBanners->isNotEmpty())
        <section class="velocity-section velocity-banners" aria-label="{{ __('frontend.banners') }}" data-velocity-reveal>
            <div class="velocity-container">
                <div class="{{ $velocityBannerType === 0 ? 'velocity-feature-grid' : 'velocity-banner-stack' }}">
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
                        @if($velocityBannerType === 0)
                            <article class="velocity-feature velocity-panel" data-velocity-reveal>
                                <span class="velocity-feature__index" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                @if(filled($velocityBanner->code))
                                {!! $velocityBanner->code !!}
                                @else
                                    @if(filled($velocityFile))
                                        @php
                                            $velocityImageUrl = route('fileView', ['path' => 'banners/'.$velocityFile]);
                                        @endphp
                                        <img
                                            class="velocity-feature__image"
                                            src="{{ $velocityImageUrl }}?w=720&h=480&r=fit"
                                            srcset="{{ $velocityImageUrl }}?w=360&h=240&r=fit 360w, {{ $velocityImageUrl }}?w=720&h=480&r=fit 720w"
                                            sizes="(max-width: 48rem) calc(100vw - 2rem), 33vw"
                                            alt=""
                                            width="720"
                                            height="480"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    @elseif(filled($velocityBanner->icon))
                                        <span class="velocity-feature__icon" aria-hidden="true">
                                            <i class="{{ $velocityBanner->icon }} velocity-feature__glyph"></i>
                                        </span>
                                    @endif
                                    @if(filled($velocityTitle))
                                        <h2 class="velocity-feature__title">{{ $velocityTitle }}</h2>
                                    @endif
                                    @if(filled($velocityDetails))
                                        <p class="velocity-muted">{{ $velocityDetails }}</p>
                                    @endif
                                    @if(filled($velocityLink))
                                        <a class="velocity-topic-card__link" href="{{ $velocityLink }}">{{ __('frontend.moreDetails') }} <span class="velocity-arrow" aria-hidden="true">→</span></a>
                                    @endif
                                @endif
                            </article>
                        @elseif($velocityBannerType === 1)
                            <figure class="velocity-banner-media velocity-panel">
                                @if(filled($velocityLink))<a href="{{ $velocityLink }}">@endif
                                @if(filled($velocityFile))
                                    @php
                                        $velocityImageUrl = route('fileView', ['path' => 'banners/'.$velocityFile]);
                                    @endphp
                                    <img
                                        src="{{ $velocityImageUrl }}?w=1440&h=810&r=fit"
                                        srcset="{{ $velocityImageUrl }}?w=640&h=360&r=fit 640w, {{ $velocityImageUrl }}?w=960&h=540&r=fit 960w, {{ $velocityImageUrl }}?w=1440&h=810&r=fit 1440w"
                                        sizes="(max-width: 75rem) calc(100vw - 2rem), 75rem"
                                        alt="{{ $velocityTitle }}"
                                        width="1440"
                                        height="810"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                @endif
                                @if(filled($velocityLink))</a>@endif
                                @if(filled($velocityDetails))
                                    <figcaption>{{ $velocityDetails }}</figcaption>
                                @endif
                            </figure>
                        @else
                            @php
                                $velocityYoutubeId = $velocityBanner->video_type == 1 ? Helper::Get_youtube_video_id($velocityBanner->youtube_link) : null;
                                $velocityVimeoId = $velocityBanner->video_type == 2 ? Helper::Get_vimeo_video_id($velocityBanner->youtube_link) : null;
                            @endphp
                            <article class="velocity-video-card velocity-panel">
                                @if($velocityYoutubeId)
                                    <iframe src="https://www.youtube-nocookie.com/embed/{{ $velocityYoutubeId }}" title="{{ filled($velocityTitle) ? $velocityTitle : __('backend.viewDetails') }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                                @elseif($velocityVimeoId)
                                    <iframe src="https://player.vimeo.com/video/{{ $velocityVimeoId }}?title=0&amp;byline=0" title="{{ filled($velocityTitle) ? $velocityTitle : __('backend.viewDetails') }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen; picture-in-picture" allowfullscreen></iframe>
                                @elseif($velocityBanner->video_type == 0 && filled($velocityFile))
                                    <video controls playsinline preload="none" data-velocity-video-player>
                                        <source src="{{ route('fileView', ['path' => 'banners/'.$velocityFile]) }}" type="video/mp4">
                                    </video>
                                @endif
                                @if(filled($velocityDetails))
                                    <div class="velocity-rich-content velocity-video-card__caption">{!! $velocityDetails !!}</div>
                                @endif
                            </article>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endif
