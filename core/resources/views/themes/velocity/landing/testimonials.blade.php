@php
    $velocityTestimonials = ($TopicBlockContents->module_id ?? 0) > 0
        ? Helper::Topics(
            $TopicBlockContents->module_id,
            $TopicBlockContents->category_ids ?? '',
            $TopicBlockContents->records_count ?? 0,
            0,
            $TopicBlockContents->records_order ?? 0
        )
        : collect();
    $velocityCode = @Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityCarouselId = 'velocity-landing-testimonials-'.$TopicBlock->id;
    $velocitySectionUrl = ($TopicBlockContents->module_id ?? 0) > 0 ? Helper::sectionURL($TopicBlockContents->module_id) : null;
@endphp
@if($velocityTestimonials->isNotEmpty())
    @component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents, 'hasCarouselActions' => true])
        <div id="{{ $velocityCarouselId }}" class="velocity-carousel velocity-carousel--testimonials" data-velocity-carousel-track>
            @foreach($velocityTestimonials as $velocityTestimonial)
                @php
                    $velocityTitle = $velocityTestimonial->{'title_'.$velocityCode} ?: $velocityTestimonial->{'title_'.$velocityDefaultCode};
                    $velocityDetails = $velocityTestimonial->{'details_'.$velocityCode} ?: $velocityTestimonial->{'details_'.$velocityDefaultCode};
                @endphp
                <figure class="velocity-quote velocity-panel" data-velocity-carousel-slide>
                    <blockquote>{{ trim(strip_tags((string) $velocityDetails)) }}</blockquote>
                    <figcaption>
                        @if(filled($velocityTestimonial->photo_file))
                            <img src="{{ route('fileView', ['path' => 'topics/'.$velocityTestimonial->photo_file]) }}?w=96&h=96&r=fit" alt="{{ Helper::topicPhotoAlt($velocityTestimonial) }}" width="48" height="48" loading="lazy" decoding="async">
                        @endif
                        <strong>{{ $velocityTitle }}</strong>
                    </figcaption>
                </figure>
            @endforeach
        </div>
        @slot('actions')
            @include(theme_view('components.carousel-controls'), [
                'target' => $velocityCarouselId,
                'count' => $velocityTestimonials->count(),
                'autoplay' => true,
            ])
            @if($TopicBlock->more_btn_status)
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            @endif
        @endslot
    @endcomponent
@endif
