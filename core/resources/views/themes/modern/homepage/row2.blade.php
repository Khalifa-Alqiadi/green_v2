<?php
$HomeTopicsLimit = 12; // 0 = all
$HomeTopics = Helper::Topics(Helper::GeneralWebmasterSettings("home_content1_section_id"), 0, $HomeTopicsLimit);
$require_mp3_player = 0;
?>
@if(count($HomeTopics)>0)
    <section id="topics" class="section-bg modern-works">

        <div class="container">

            <div class="section-title">
                <h2>{{ __('frontend.homeContents1Title') }}</h2>
                <p>{{ __('frontend.homeContents1desc') }}</p>
            </div>
            <div class="modern-works-slider swiper">
                <div class="swiper-wrapper">
                    <?php
                    $section_url = "";
                    ?>
                    @foreach($HomeTopics as $Topic)
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
                        <div class="swiper-slide modern-works-slide">
                            @include(theme_view("topic.works-card"),["Topic"=>$Topic])
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

            <div class="row">
                <div class="col-lg-12">
                    <div class="more-btn">
                        <a href="{{ url($section_url) }}" class="btn btn-theme">
                            {{ __('frontend.viewMore') }}
                            <i class="fa-solid fa-arrow-right-long staff-cta-arrow" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>

        </div>
    </section>
    @if ($require_mp3_player)
        @push('before-styles')
            <link rel="stylesheet"
                  href="{{ theme_asset('vendor/green-audio-player/css/green-audio-player.min.css') }}?v={{ Helper::system_version() }}"/>
        @endpush
        @push('after-scripts')
            <script
                src="{{ theme_asset('vendor/green-audio-player/js/green-audio-player.min.js') }}?v={{ Helper::system_version() }}"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    GreenAudioPlayer.init({
                        selector: '.audio-player',
                        stopOthersOnPlay: true,
                        showTooltips: true,
                    });
                });
            </script>
        @endpush
    @endif
@endif
