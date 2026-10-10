@php
    extract(require resource_path('views/themes/modern/landing/_yh-data.php'));
@endphp

@if($yhTopics->isNotEmpty())
    @php
        $yhCategorizedArticles = $yhTopics->filter(function ($item) {
            return $item->category($item->id) !== null;
        });
        $yhArticleGroups = ($yhCategorizedArticles->isNotEmpty() ? $yhCategorizedArticles : $yhTopics)->groupBy(function ($item) {
            return optional($item->category($item->id))->id ?: 'uncategorized';
        });
        $yhSliderColumns = min(5, max(2, (int) data_get($TopicBlockContents, 'slider_columns', 3)));
        $yhSliderAutoplay = (int) data_get($TopicBlockContents, 'slider_autoplay', 1) === 1;
        $yhSliderSpeed = min(12000, max(1500, (int) data_get($TopicBlockContents, 'slider_speed', 5000)));
        $yhSliderClass = 'yh-articles-swiper-'.$TopicBlock->id;
    @endphp

    <section id="landing-block-{{ $TopicBlock->id }}" class="yh-home-section yh-articles-columns {{ $TopicBlock->css_classes }}">
        <span class="yh-articles-columns__pattern" aria-hidden="true"></span>

        <div class="container">
            <header class="yh-articles-columns__head meu-reveal">
                <div class="yh-articles-columns__title">
                    <span><i></i> {{ __('yemen_home.opinions_eyebrow') }}</span>
                    @if($TopicBlock->title_status)
                        <h2>{{ $yhBlockTitle ?: __('yemen_home.opinions_title') }}</h2>
                    @endif
                    @if($TopicBlock->desc_status && $yhBlockDescription)
                        <p>{{ $yhBlockDescription }}</p>
                    @endif
                </div>

                <div class="yh-articles-columns__tools">
                    @if($TopicBlock->more_btn_status)
                        <a href="{{ $yhSectionUrl }}">{{ __('yemen_home.opinions_all') }} <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i></a>
                    @endif
                    <div class="yh-articles-columns__nav">
                        <button class="yh-articles-prev-{{ $TopicBlock->id }}" type="button" aria-label="{{ __('yemen_home.previous') }}"><i class="fa-solid fa-arrow-right-long"></i></button>
                        <button class="yh-articles-next-{{ $TopicBlock->id }}" type="button" aria-label="{{ __('yemen_home.next') }}"><i class="fa-solid fa-arrow-left-long"></i></button>
                    </div>
                </div>
            </header>

            <div class="swiper {{ $yhSliderClass }} yh-articles-columns__slider" data-columns="{{ $yhSliderColumns }}">
                <div class="swiper-wrapper">
                    @foreach($yhArticleGroups as $categoryId => $articles)
                        @php
                            $featuredArticle = $articles->first();
                            $secondaryArticle = $articles->skip(1)->first();
                            $category = $featuredArticle->category($featuredArticle->id);
                            $categoryTitle = optional($category)->{$yhTitleField} ?: optional($category)->{$yhFallbackTitleField} ?: __('yemen_home.opinions_category');
                            $author = $featuredArticle->authors->first();
                            $authorName = $author?->{'name_'.$yhLanguage} ?: $author?->{'name_'.$yhFallbackLanguage} ?: __('yemen_home.opinions_editorial_team');
                            $columnMoreUrl = $secondaryArticle
                                ? Helper::topicURL($secondaryArticle->id, '', $secondaryArticle)
                                : ($category ? Helper::categoryURL($category->id, '', $category) : $yhSectionUrl);
                            $columnMoreLabel = $secondaryArticle ? __('yemen_home.opinions_also_in', ['category' => $categoryTitle]) : __('yemen_home.opinions_more_section');
                            $columnMoreTitle = $secondaryArticle ? $yhTopicTitle($secondaryArticle) : __('yemen_home.opinions_explore', ['category' => $categoryTitle]);
                        @endphp
                        <div class="swiper-slide yh-article-column">
                            <article>
                                <header>
                                    <span class="yh-article-column__number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    @if($category)
                                        <a href="{{ Helper::categoryURL($category->id, '', $category) }}">{{ $categoryTitle }}</a>
                                    @else
                                        <span>{{ $categoryTitle }}</span>
                                    @endif
                                </header>

                                <a class="yh-article-column__media" href="{{ Helper::topicURL($featuredArticle->id, '', $featuredArticle) }}">
                                    @if($featuredArticle->photo_file)
                                        <img src="{{ route('fileView', ['path' => 'topics/'.$featuredArticle->photo_file]) }}" alt="" loading="lazy" decoding="async">
                                    @else
                                        <span><i class="fa-solid fa-feather-pointed"></i></span>
                                    @endif
                                    <b>{{ __('yemen_home.read') }}</b>
                                </a>

                                <div class="yh-article-column__body">
                                    <h3><a href="{{ Helper::topicURL($featuredArticle->id, '', $featuredArticle) }}">{{ $yhTopicTitle($featuredArticle) }}</a></h3>
                                    <p>{!! str_replace('"', " ", \Illuminate\Support\Str::limit(strip_tags($yhTopicDetails($featuredArticle)), 118)) !!}</p>
                                    <footer>
                                        <span class="yh-article-column__author">{{ mb_substr($authorName, 0, 1) }}</span>
                                        <span><b>{{ $authorName }}</b><time>{{ $featuredArticle->date }}</time></span>
                                        <i class="fa-solid fa-arrow-left-long"></i>
                                    </footer>
                                </div>

                                <a class="yh-article-column__more" href="{{ $columnMoreUrl }}">
                                    <span><small>{{ $columnMoreLabel }}</small><strong>{{ $columnMoreTitle }}</strong></span>
                                    <i class="fa-solid fa-arrow-left"></i>
                                </a>
                            </article>
                        </div>
                    @endforeach
                </div>
                <div class="swiper-pagination yh-articles-pagination-{{ $TopicBlock->id }}"></div>
            </div>
        </div>
    </section>

    @push('after-scripts')
        <script>
            (function () {
                function initArticleColumns{{ $TopicBlock->id }}() {
                    var slider = document.querySelector('.{{ $yhSliderClass }}');
                    if (!slider || slider.swiper || typeof window.Swiper === 'undefined') return;

                    new Swiper(slider, {
                        slidesPerView: 1.08,
                        spaceBetween: 14,
                        speed: 720,
                        watchOverflow: true,
                        grabCursor: true,
                        navigation: {
                            nextEl: '.yh-articles-next-{{ $TopicBlock->id }}',
                            prevEl: '.yh-articles-prev-{{ $TopicBlock->id }}'
                        },
                        pagination: {
                            el: '.yh-articles-pagination-{{ $TopicBlock->id }}',
                            clickable: true
                        },
                        autoplay: @json($yhSliderAutoplay ? ['delay' => $yhSliderSpeed, 'disableOnInteraction' => false, 'pauseOnMouseEnter' => true] : false),
                        breakpoints: {
                            576: { slidesPerView: 1.55, spaceBetween: 16 },
                            768: { slidesPerView: 2.15, spaceBetween: 18 },
                            992: { slidesPerView: Math.min(3, {{ $yhSliderColumns }}), spaceBetween: 20 },
                            1200: { slidesPerView: {{ $yhSliderColumns }}, spaceBetween: 22 }
                        }
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initArticleColumns{{ $TopicBlock->id }});
                } else {
                    initArticleColumns{{ $TopicBlock->id }}();
                }
            })();
        </script>
    @endpush
@endif
