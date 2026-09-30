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
        @$TopicBlockContents->records_count, 0, 2);
    $section_url = Helper::sectionURL(@$TopicBlockContents->module_id);
}
?>

@if(count($BlockTopics) >0)
    <section id="landing-block-{{ @$TopicBlock->id }}" class="staff landing-block {{ @$TopicBlock->css_classes  }}" style="{{ $block_style }}">
        <div class="container-fluid px-0">
            <div class="staff-showcase">
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

                <div class="staff-stage">
                    <div class="staff-slider swiper">
                        <div class="swiper-wrapper staff-grid">
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
                            @include(theme_view('topic.staff-card'), compact('Topic', 'title', 'details', 'topic_link_url'))
                        @endforeach
                        </div>

                        <div class="staff-slider-controls">
                            <button type="button" class="staff-slider-button staff-slider-prev"
                                    aria-label="{{ html_entity_decode(__('pagination.previous')) }}">
                                <i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
                            </button>
                            <div class="staff-slider-pagination" aria-hidden="true"></div>
                            <button type="button" class="staff-slider-button staff-slider-next"
                                    aria-label="{{ html_entity_decode(__('pagination.next')) }}">
                                <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    @if (@$TopicBlock->more_btn_status)
                        <div class="more-btn">
                            <a href="{{ url($section_url) }}" class="btn btn-theme staff-more-btn">
                                {{ __('frontend.viewMore') }}
                                <i class="fa-solid fa-arrow-right-long staff-cta-arrow" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
