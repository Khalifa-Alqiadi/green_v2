@php
    $velocityPhotoTopics = Helper::Topics(Helper::GeneralWebmasterSettings('home_content2_section_id'), 0, 12, 1);
    $velocitySectionUrl = $velocityPhotoTopics->isNotEmpty() ? Helper::sectionURL($velocityPhotoTopics->first()->webmaster_id) : null;
    $velocityPhotos = collect();
    foreach ($velocityPhotoTopics as $velocityPhotoTopic) {
        $velocityPhotoTitle = $velocityPhotoTopic->{$title_var} ?: $velocityPhotoTopic->{$title_var2};
        foreach ($velocityPhotoTopic->photos as $velocityPhoto) {
            if ($velocityPhotos->count() >= 12) {
                break 2;
            }
            $velocityPhotos->push(['file' => $velocityPhoto->file, 'title' => $velocityPhotoTitle]);
        }
    }
@endphp
@if($velocityPhotos->isNotEmpty())
    <section id="gallery" class="velocity-section velocity-gallery-section" data-velocity-reveal>
        <div class="velocity-container--wide velocity-section-layout">
            @include(theme_view('components.section-heading'), [
                'title' => __('frontend.homeContents2Title'),
                'description' => __('frontend.homeContents2desc'),
            ])
            <div class="velocity-gallery-grid">
                @foreach($velocityPhotos as $velocityPhoto)
                    <a class="velocity-gallery-grid__item" href="{{ route('fileView', ['path' => 'topics/'.$velocityPhoto['file']]) }}" data-velocity-lightbox data-gallery="velocity-home-gallery" data-title="{{ $velocityPhoto['title'] }}">
                        <img src="{{ route('fileView', ['path' => 'topics/'.$velocityPhoto['file']]) }}?w=700&h=700&r=fit" alt="{{ $velocityPhoto['title'] }}" width="700" height="700" loading="lazy" decoding="async">
                    </a>
                @endforeach
            </div>
            <div class="velocity-section-actions">
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            </div>
        </div>
    </section>
@endif
