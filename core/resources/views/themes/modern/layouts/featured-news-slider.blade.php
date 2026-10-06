@php
    $featuredCarouselId = 'featuredNewsCarousel-'.$TopicBlock->id;
    $featuredSlideCount = $featuredNews->count();
    $featuredSliderLabel = $TopicBlock->title_status && $yhBlockTitle
        ? $yhBlockTitle
        : __('yemen_home.featured_news_slider');
@endphp


<section id="landing-block-{{ $TopicBlock->id }}" class="meu-hero scholars-editorial-hero yh-featured-news {{ $TopicBlock->css_classes }}" aria-label="{{ $featuredSliderLabel }}" style="--featured-slide-duration: {{ $featuredSliderSpeed }}ms">
    <div class="hero-fx" aria-hidden="true">
        <span class="hero-fx-grid"></span>
        <span class="hero-fx-rosette"></span>
        <span class="hero-fx-arch"></span>
    </div>
    <div class="hero-container hero-slider">
        <div id="{{ $featuredCarouselId }}" class="carousel slide carousel-fade {{ $featuredSlideCount === 1 ? 'hero-single-slide' : '' }}"
             @if($featuredSliderAutoplay && $featuredSlideCount > 1) data-bs-ride="carousel" data-bs-interval="{{ $featuredSliderSpeed }}" @endif>
            <div class="carousel-inner">
                @foreach($featuredNews as $featuredItem)
                    @php
                        $featuredTitle = $yhTopicTitle($featuredItem);
                        $featuredDetails = \Illuminate\Support\Str::limit(trim(strip_tags((string) $yhTopicDetails($featuredItem))), 170);
                        $featuredUrl = Helper::topicURL($featuredItem->id, '', $featuredItem);
                    @endphp
                    <article class="carousel-item {{ $loop->first ? 'active' : '' }}">
                        @if($featuredItem->photo_file)
                            <img class="hero-image-stage" src="{{ route("fileView",
                                ["path" => 'topics/'.$featuredItem->photo_file]) }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async">
                        @else
                            <div class="hero-image-stage yh-featured-news__image-fallback" aria-hidden="true"><i class="fa-regular fa-newspaper"></i></div>
                        @endif
                        <span class="hero-image-frame" aria-hidden="true"></span>
                        <div class="carousel-container">
                            <div class="carousel-content container">
                                <div class="slider-content">
                                    <div class="hero-slide-meta">
                                        <span class="hero-slide-meta-mark" aria-hidden="true"></span>
                                        <span class="hero-slide-brand">{{ $featuredSliderLabel }}</span>
                                        <span class="hero-slide-count">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} <small>/ {{ str_pad((string) $featuredSlideCount, 2, '0', STR_PAD_LEFT) }}</small></span>
                                    </div>
                                    <h2 class="slider-title">{{ $featuredTitle }}</h2>
                                    @if($featuredDetails)
                                        <p class="slider-details">{{ $featuredDetails }}</p>
                                    @endif
                                    <a href="{{ $featuredUrl }}" class="btn-theme slider-link">
                                        <span>{{ __('yemen_home.news_read') }}</span>
                                        <i class="bi bi-arrow-{{ @Helper::currentLanguage()->right }}" aria-hidden="true"></i>
                                    </a>
                                    @if($featuredSliderAutoplay && $featuredSlideCount > 1)
                                        <div class="hero-slide-progress" aria-hidden="true"><span></span></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            @if($featuredSlideCount > 1)
                <div class="carousel-indicators">
                    @foreach($featuredNews as $featuredItem)
                        <button type="button" data-bs-target="#{{ $featuredCarouselId }}" data-bs-slide-to="{{ $loop->index }}"
                                class="{{ $loop->first ? 'active' : '' }}" @if($loop->first) aria-current="true" @endif
                                aria-label="{{ __('yemen_home.news_featured') }} {{ $loop->iteration }}"></button>
                    @endforeach
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#{{ $featuredCarouselId }}" data-bs-slide="prev" aria-label="{{ __('yemen_home.previous') }}">
                    <span class="carousel-control-prev-icon bi bi-chevron-left" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#{{ $featuredCarouselId }}" data-bs-slide="next" aria-label="{{ __('yemen_home.next') }}">
                    <span class="carousel-control-next-icon bi bi-chevron-right" aria-hidden="true"></span>
                </button>
            @endif
        </div>
    </div>
</section>
