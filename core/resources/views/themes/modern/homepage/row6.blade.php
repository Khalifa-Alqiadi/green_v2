<?php
$TestimonialsLimit = 0; // 0 = all
$Testimonials = Helper::Topics(Helper::GeneralWebmasterSettings("home_content6_section_id"), 0, $TestimonialsLimit, 1);
$testimonialRows = count($Testimonials) > 6 ? [[], []] : [[]];
$testimonialRowCount = count($testimonialRows);
$testimonialIndex = 0;
foreach ($Testimonials as $Topic) {
    $testimonialRows[$testimonialIndex % $testimonialRowCount][] = $Topic;
    $testimonialIndex++;
}
?>
@if(count($Testimonials)>0)
    <section id="testimonials" class="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>{{ __('frontend.homeTestimonialsTitle') }}</h2>
                <p>{{ __('frontend.homeTestimonialsDesc') }}</p>
            </div>
        </div>

        <div class="testimonials-marquee-wrap" data-aos="fade-up" data-aos-delay="100">
            <?php
            $section_url = "";
            ?>
            @foreach($testimonialRows as $rowIndex => $TestimonialsRow)
                @if(count($TestimonialsRow) > 0)
                    <div class="testimonials-marquee swiper {{ $rowIndex == 1 ? 'testimonials-marquee-reverse' : '' }}"
                         data-marquee-direction="{{ $rowIndex == 1 ? 'reverse' : 'forward' }}">
                        <div class="swiper-wrapper">
                            @foreach($TestimonialsRow as $Topic)
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
                                if ($section_url == "") {
                                    $section_url = Helper::sectionURL($Topic->webmaster_id);
                                }
                                $topic_link_url = Helper::topicURL($Topic->id,"",$Topic);
                                $HomeSectionType = @$Topic->webmasterSection->type;
                                if (!@$require_mp3_player && $HomeSectionType == 3) {
                                    $require_mp3_player = 1;
                                }
                                ?>
                                <div class="swiper-slide">
                                    <article class="testimonial-card">
                                        @if(strip_tags($Topic->$details) !="")
                                            <p class="testimonial-quote">
                                                <i class="fa-solid fa-quote-left quote-icon-left"></i>
                                                <span>{!! strip_tags($Topic->$details) !!}</span>
                                            </p>
                                        @endif
                                        <div class="testimonial-author">
                                            @if($Topic->photo_file !="")
                                                <img class="testimonial-author-photo"
                                                     src="{{ route("fileView",["path" =>'topics/'.$Topic->photo_file ]) }}?w=88&h=88&r=fit" width="88" height="88" loading="lazy"
                                                     alt="{{ Helper::topicPhotoAlt($Topic) }}"/>
                                            @else
                                                <span class="testimonial-author-photo testimonial-author-photo-placeholder" aria-hidden="true">
                                                    {{ mb_substr(strip_tags($title), 0, 1) }}
                                                </span>
                                            @endif
                                            <h3 class="testimonial-author-title">{{ $title }}</h3>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endif
