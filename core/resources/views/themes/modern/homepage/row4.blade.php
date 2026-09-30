<?php
$HomePhotosLimit = 6; ; // 0 = all
$HomePhotos = Helper::Topics(Helper::GeneralWebmasterSettings("home_content2_section_id"), 0, $HomePhotosLimit, 1);
?>
@if(count($HomePhotos)>0)
    <section id="gallery" class="gallery">
        <div class="container">

            <div class="section-title">
                <h2>{{ __('frontend.homeContents2Title') }}</h2>
                <p>{{ __('frontend.homeContents2desc') }}</p>
            </div>
        </div>

        <div class="container-fluid gallery-showcase">
            <div class="gallery-deck" role="list">
                <?php
                $section_url = "";
                $ph_count = 0;
                ?>
                @foreach($HomePhotos as $HomePhoto)
                    <?php
                    if ($HomePhoto->$title_var != "") {
                        $title = $HomePhoto->$title_var;
                    } else {
                        $title = $HomePhoto->$title_var2;
                    }

                    if ($section_url == "") {
                        $section_url = Helper::sectionURL($HomePhoto->webmaster_id);
                    }
                    ?>
                    @foreach($HomePhoto->photos as $photo)
                        @if($ph_count<$HomePhotosLimit)
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
            <div class="row mt-3">
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
@endif
