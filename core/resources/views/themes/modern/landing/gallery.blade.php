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
$PhotosLimit = @$TopicBlockContents->records_count;
if (@$TopicBlockContents->module_id) {
    $BlockTopics = Helper::Topics(@$TopicBlockContents->module_id, @$TopicBlockContents->category_ids,
        @$TopicBlockContents->records_count, 0, @$TopicBlockContents->records_order);
    $section_url = Helper::sectionURL(@$TopicBlockContents->module_id);
}
?>

@if(count($BlockTopics) >0)
    <section id="landing-block-{{ @$TopicBlock->id }}" class="gallery landing-block {{ @$TopicBlock->css_classes  }}" style="{{ $block_style }}">
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

        <div class="container-fluid gallery-showcase">
            <div class="gallery-deck" role="list">
                    <?php
                    $section_url = "";
                    $ph_count = 0;
                    ?>
                @foreach($BlockTopics as $BlockTopic)
                        <?php
                        if ($BlockTopic->$title_var != "") {
                            $title = $BlockTopic->$title_var;
                        } else {
                            $title = $BlockTopic->$title_var2;
                        }

                        if ($section_url == "") {
                            $section_url = Helper::sectionURL($BlockTopic->webmaster_id);
                        }
                        ?>
                    @foreach($BlockTopic->photos as $photo)
                        @if($ph_count<$PhotosLimit)
                            <div class="gallery-deck-item" role="listitem">
                                <div class="gallery-item">
                                    <a href="{{ route("fileView",["path" =>'topics/'.$photo->file ]) }}"
                                       class="galelry-lightbox gallery-card-link" title="{{ $title }}"
                                       aria-label="{{ $title }}">
                                        <img src="{{ route("fileView",["path" =>'topics/'.$photo->file ]) }}?w=720&h=960"
                                             width="720" height="960" loading="lazy"
                                             sizes="(min-width: 992px) 28vw, (min-width: 480px) 50vw, 82vw"
                                             alt="{{ $title }}" class="gallery-card-image">
                                        <span class="gallery-card-action" aria-hidden="true">
                                            <i class="bi bi-arrows-fullscreen"></i>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        @else
                            @break
                        @endif
                            <?php
                            $ph_count++;
                            ?>
                    @endforeach
                @endforeach

            </div>
            @if (@$TopicBlock->more_btn_status)
                <div class="row mt-3">
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
