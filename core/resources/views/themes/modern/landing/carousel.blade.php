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
$isMagazineCarousel = (int) (@$TopicBlockContents->module_id) === 18;
if (@$TopicBlockContents->module_id) {
    $BlockTopics = Helper::Topics(@$TopicBlockContents->module_id, @$TopicBlockContents->category_ids,
        @$TopicBlockContents->records_count, 0, @$TopicBlockContents->records_order);
    $section_url = Helper::sectionURL(@$TopicBlockContents->module_id);
}
$isVideoCarousel = (int) data_get(collect($BlockTopics)->first(), 'webmasterSection.type') === 2;
?>

@if(count($BlockTopics) >0)
    <section id="landing-block-{{ @$TopicBlock->id }}" class="landing-block modern-works{{ $isMagazineCarousel ? ' modern-works--magazine' : '' }}{{ $isVideoCarousel ? ' yh-video-showcase' : '' }} {{ @$TopicBlock->css_classes  }}" style="{{ $block_style }}">
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
            <div class="modern-works-slider{{ $isMagazineCarousel ? ' modern-works-slider--magazine' : '' }}{{ $isVideoCarousel ? ' modern-works-slider--videos' : '' }} swiper">
                <div class="swiper-wrapper">
                    @foreach($BlockTopics as $Topic)
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
                            $topic_link_url = Helper::topicURL($Topic->id, "", $Topic);
                            $HomeSectionType = @$Topic->webmasterSection->type;
                            if (!@$require_mp3_player && $HomeSectionType == 3) {
                                $require_mp3_player = 1;
                            }
                            ?>
                        <div class="swiper-slide modern-works-slide">
                            @if($isVideoCarousel)
                                @include(theme_view('topic.video-card'), ['Topic' => $Topic])
                            @elseif($isMagazineCarousel)
                                @include(theme_view('topic.magazine-card'), ['Topic' => $Topic])
                            @else
                                @include(theme_view("topic.works-card"),["Topic"=>$Topic])
                            @endif
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
