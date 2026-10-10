@php
    extract(require resource_path('views/themes/modern/landing/_yh-data.php'));
@endphp
@php($isInfographic = strtolower((string) data_get($TopicBlockContents, 'view_style')) === 'yhinfographics' || str_contains((string) $TopicBlock->css_classes, 'infographics'))

@if($yhTopics->isNotEmpty())
    <section id="landing-block-{{ $TopicBlock->id }}" class="yh-home-section yh-visuals {{ $TopicBlock->css_classes }}">
        <div class="container">
            @include(theme_view('landing._yh-heading'), ['yhEyebrow' => data_get($TopicBlockContents, 'eyebrow_'.$yhLanguage) ?: __('yemen_home.visuals_eyebrow')])

            @if($isInfographic)
                <div class="yh-infographics__grid meu-stagger">
                    @foreach($yhTopics as $item)
                        <a class="yh-infographic-card{{ $item->photo_file ? ' galelry-lightbox' : '' }}"
                           href="{{ $item->photo_file ? route('fileView', ['path' => 'topics/'.$item->photo_file]) : Helper::topicURL($item->id, '', $item) }}"
                           @if($item->photo_file) data-gallery="yh-infographics-{{ $TopicBlock->id }}" title="{{ $yhTopicTitle($item) }}" @endif>
                            <figure>
                                @if($item->photo_file)
                                    <img src="{{ route('fileView', ['path' => 'topics/'.$item->photo_file]) }}" alt="{{ $yhTopicTitle($item) }}" loading="lazy" decoding="async">
                                @else
                                    <span class="yh-infographic-card__placeholder"><i class="fa-regular fa-image"></i></span>
                                @endif
                                <span class="yh-infographic-card__expand" aria-hidden="true"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></span>
                            </figure>
                            {{--<div class="yh-infographic-card__copy">
                                 <small><i></i>{{ __('yemen_home.visuals_infographic') }}</small>
                                <h3>{{ $yhTopicTitle($item) }}</h3>
                                <footer>
                                    <time>{{ $item->date }}</time>
                                    <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i>
                                </footer>
                            </div> --}}
                        </a>
                    @endforeach
                </div>
            @else
                <div class="modern-works-slider modern-works-slider--videos swiper meu-stagger">
                    <div class="swiper-wrapper">
                        @foreach($yhTopics as $item)
                            <div class="swiper-slide modern-works-slide">
                                @include(theme_view('topic.video-card'), [
                                    'Topic' => $item,
                                    'title_var' => $yhTitleField,
                                    'title_var2' => $yhFallbackTitleField,
                                ])
                            </div>
                        @endforeach
                    </div>
                    <div class="modern-works-slider-controls">
                        <button type="button" class="modern-works-slider-button modern-works-slider-prev"
                                aria-label="{{ html_entity_decode(__('pagination.previous')) }}">
                            <i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
                        </button>
                        <div class="modern-works-slider-pagination" aria-hidden="true"></div>
                        <button type="button" class="modern-works-slider-button modern-works-slider-next"
                                aria-label="{{ html_entity_decode(__('pagination.next')) }}">
                            <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
