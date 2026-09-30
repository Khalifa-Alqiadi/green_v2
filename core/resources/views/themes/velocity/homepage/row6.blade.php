@php
    $velocityTestimonials = Helper::Topics(Helper::GeneralWebmasterSettings('home_content6_section_id'), 0, 0, 1);
    $velocityCarouselId = 'velocity-home-testimonials';
@endphp
@if($velocityTestimonials->isNotEmpty())
    <section id="testimonials" class="velocity-section" data-velocity-reveal>
        <div class="velocity-container velocity-section-layout">
            @include(theme_view('components.section-heading'), [
                'title' => __('frontend.homeTestimonialsTitle'),
                'description' => __('frontend.homeTestimonialsDesc'),
            ])
            <div id="{{ $velocityCarouselId }}" class="velocity-carousel velocity-carousel--testimonials" data-velocity-carousel-track>
                @foreach($velocityTestimonials as $velocityTestimonial)
                    @php
                        $velocityTitle = $velocityTestimonial->{$title_var} ?: $velocityTestimonial->{$title_var2};
                        $velocityDetailsField = filled($velocityTestimonial->{$details_var}) ? $details_var : $details_var2;
                    @endphp
                    <figure class="velocity-quote velocity-panel" data-velocity-carousel-slide>
                        <blockquote>{{ trim(strip_tags((string) $velocityTestimonial->{$velocityDetailsField})) }}</blockquote>
                        <figcaption>
                            @if(filled($velocityTestimonial->photo_file))
                                <img src="{{ route('fileView', ['path' => 'topics/'.$velocityTestimonial->photo_file]) }}?w=96&h=96&r=fit" alt="{{ Helper::topicPhotoAlt($velocityTestimonial) }}" width="48" height="48" loading="lazy" decoding="async">
                            @endif
                            <strong>{{ $velocityTitle }}</strong>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
            <div class="velocity-section-actions velocity-section-actions--carousel">
                @include(theme_view('components.carousel-controls'), [
                    'target' => $velocityCarouselId,
                    'count' => $velocityTestimonials->count(),
                    'autoplay' => true,
                ])
            </div>
        </div>
    </section>
@endif
