@php
    $velocityTopics = Helper::Topics(Helper::GeneralWebmasterSettings('home_content1_section_id'), 0, 12);
    $velocitySectionUrl = $velocityTopics->isNotEmpty() ? Helper::sectionURL($velocityTopics->first()->webmaster_id) : null;
    $velocityCarouselId = 'velocity-home-topics';
@endphp
@if($velocityTopics->isNotEmpty())
    <section id="topics" class="velocity-section velocity-section--muted" data-velocity-reveal>
        <div class="velocity-container velocity-section-layout">
            @include(theme_view('components.section-heading'), [
                'title' => __('frontend.homeContents1Title'),
                'description' => __('frontend.homeContents1desc'),
            ])
            <div id="{{ $velocityCarouselId }}" class="velocity-carousel velocity-carousel--cards" data-velocity-carousel-track>
                @foreach($velocityTopics as $Topic)
                    <div class="velocity-carousel__item" data-velocity-carousel-slide>
                        @include(theme_view('components.topic-card'), ['topic' => $Topic])
                    </div>
                @endforeach
            </div>
            <div class="velocity-section-actions velocity-section-actions--carousel">
                @include(theme_view('components.carousel-controls'), [
                    'target' => $velocityCarouselId,
                    'count' => $velocityTopics->count(),
                ])
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            </div>
        </div>
    </section>
@endif
