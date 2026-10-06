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
                        <a class="yh-infographic-card" href="{{ Helper::topicURL($item->id, '', $item) }}">
                            <figure>
                                @if($item->photo_file)
                                    <img src="{{ route('fileView', ['path' => 'topics/'.$item->photo_file]) }}" alt="" loading="lazy" decoding="async">
                                @else
                                    <span class="yh-infographic-card__placeholder"><i class="fa-regular fa-image"></i></span>
                                @endif
                                <b class="yh-infographic-card__number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</b>
                                <span class="yh-infographic-card__expand" aria-hidden="true"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></span>
                            </figure>
                            <div class="yh-infographic-card__copy">
                                <small><i></i>{{ __('yemen_home.visuals_infographic') }}</small>
                                <h3>{{ $yhTopicTitle($item) }}</h3>
                                <footer>
                                    <time>{{ $item->date }}</time>
                                    <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i>
                                </footer>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                @php($featuredVisual = $yhTopics->first())
                <div class="yh-video-wall meu-stagger">
                    <a class="yh-video-wall__featured" href="{{ Helper::topicURL($featuredVisual->id, '', $featuredVisual) }}">
                        @if($featuredVisual->photo_file)
                            <img src="{{ route('fileView', ['path' => 'topics/'.$featuredVisual->photo_file]) }}" alt="" loading="lazy" decoding="async">
                        @else
                            <i class="fa-regular fa-image yh-video-wall__placeholder" aria-hidden="true"></i>
                        @endif
                        <span class="yh-video-wall__play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                        <div><small>{{ __('yemen_home.visuals_coverage') }}</small><h3>{{ $yhTopicTitle($featuredVisual) }}</h3></div>
                    </a>
                    <div class="yh-video-wall__list">
                        @foreach($yhTopics->slice(1, 3) as $item)
                            <a href="{{ Helper::topicURL($item->id, '', $item) }}">
                                <figure>
                                    @if($item->photo_file)
                                        <img src="{{ URL::asset('uploads/topics/'.$item->photo_file) }}" alt="" loading="lazy" decoding="async">
                                    @else
                                        <i class="fa-regular fa-image yh-video-wall__placeholder" aria-hidden="true"></i>
                                    @endif
                                    <span aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                                </figure>
                                <span class="yh-video-wall__item-copy">
                                    <small>{{ str_pad((string) ($loop->iteration + 1), 2, '0', STR_PAD_LEFT) }} / {{ __('yemen_home.visuals_coverage') }}</small>
                                    <strong>{{ $yhTopicTitle($item) }}</strong>
                                    <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
