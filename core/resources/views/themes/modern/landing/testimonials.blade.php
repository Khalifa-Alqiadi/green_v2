<?php
$title_var = "title_".@Helper::currentLanguage()->code;
$title_var2 = "title_".config('smartend.default_language');
$details_var = "details_".@Helper::currentLanguage()->code;
$details_var2 = "details_".config('smartend.default_language');

$block_style = "";
if (@$TopicBlock->bg_color != "") {
    $block_style = "background-color: ".@$TopicBlock->bg_color.";";
}
if (@$TopicBlock->divider_status) {
    @$TopicBlock->css_classes .= " divider";
}
if (@$TopicBlock->image_status && @$TopicBlockContents->{"bg_".@Helper::currentLanguage()->code} != "") {
    $block_style .= "background-image: url(".route("fileView",
            ["path" => 'topics/'.@$TopicBlockContents->{"bg_".@Helper::currentLanguage()->code}]).");background-size:cover;background-repeat: no-repeat;background-position: center top;";
}
$BlockTopics = [];
$section_url = "";
if (@$TopicBlockContents->module_id) {
    $BlockTopics = Helper::Topics(@$TopicBlockContents->module_id, @$TopicBlockContents->category_ids,
        @$TopicBlockContents->records_count, 0, @$TopicBlockContents->records_order);
    $section_url = Helper::sectionURL(@$TopicBlockContents->module_id);
}
$testimonialRows = count($BlockTopics) > 6 ? [[], []] : [[]];
$testimonialRowCount = count($testimonialRows);
$testimonialIndex = 0;
foreach ($BlockTopics as $Topic) {
    $testimonialRows[$testimonialIndex % $testimonialRowCount][] = $Topic;
    $testimonialIndex++;
}
?>

@if(count($BlockTopics) >0)
    <section id="landing-block-{{ @$TopicBlock->id }}" class="testimonials landing-block {{ @$TopicBlock->css_classes  }}" style="{{ $block_style }}">
        <div class="container">
            @if(@$TopicBlock->title_status || @$TopicBlock->desc_status)
                <div class="section-title">
                    @if(@$TopicBlock->title_status)
                        <h2>{{ @$TopicBlockContents->{"title_".@Helper::currentLanguage()->code} }}</h2>
                    @endif
                    @if(@$TopicBlock->desc_status)
                        <p>{!! nl2br(@$TopicBlockContents->{"desc_".@Helper::currentLanguage()->code}) !!}</p>
                    @endif
                </div>
            @endif
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
                                $topic_link_url = Helper::topicURL($Topic->id, "", $Topic);
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

        <div class="container">
            @if (@$TopicBlock->more_btn_status)
                <div class="row">
                    <div class="col-lg-12">
                        <div class="more-btn">
                            <a href="{{ url($section_url) }}" class="btn btn-theme">
                                {{ __('frontend.viewMore') }}
                                <i class="fa-solid fa-arrow-right-long staff-cta-arrow" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
