<?php
$StaffLimit = 3; // 0 = all
$Staff = Helper::Topics(Helper::GeneralWebmasterSettings("home_content5_section_id"), 0, $StaffLimit, 0, 2);
?>
@if(count($Staff)>0)
    <section id="staff" class="staff">
        <div class="container-fluid px-0">
            <div class="staff-showcase">
                <div class="section-title">
                    <h2>{{ __('frontend.homeStaffTitle') }}</h2>
                    <p>{{ __('frontend.homeStaffDesc') }}</p>
                </div>

                <div class="staff-stage">
                    <div class="staff-slider swiper">
                        <div class="swiper-wrapper staff-grid">
                    <?php
                    $section_url = "";
                    ?>
                    @foreach($Staff as $Topic)
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

                    <div class="more-btn">
                        <a href="{{ url($section_url) }}" class="btn btn-theme staff-more-btn">
                            {{ __('frontend.viewMore') }}
                            <i class="fa-solid fa-arrow-right-long staff-cta-arrow" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
