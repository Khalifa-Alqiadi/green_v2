@php
    $velocityGalleryTopics = ($TopicBlockContents->module_id ?? 0) > 0
        ? Helper::Topics(
            $TopicBlockContents->module_id,
            $TopicBlockContents->category_ids ?? '',
            $TopicBlockContents->records_count ?? 12,
            0,
            $TopicBlockContents->records_order ?? 0
        )
        : collect();
    $velocityPhotoLimit = max(1, (int) ($TopicBlockContents->records_count ?? 12));
    $velocityCode = @Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityGalleryPhotos = collect();
    foreach ($velocityGalleryTopics as $velocityGalleryTopic) {
        $velocityGalleryTitle = $velocityGalleryTopic->{'title_'.$velocityCode} ?: $velocityGalleryTopic->{'title_'.$velocityDefaultCode};
        foreach ($velocityGalleryTopic->photos as $velocityGalleryPhoto) {
            if ($velocityGalleryPhotos->count() >= $velocityPhotoLimit) {
                break 2;
            }
            $velocityGalleryPhotos->push(['file' => $velocityGalleryPhoto->file, 'title' => $velocityGalleryTitle]);
        }
    }
    $velocitySectionUrl = ($TopicBlockContents->module_id ?? 0) > 0 ? Helper::sectionURL($TopicBlockContents->module_id) : null;
@endphp
@if($velocityGalleryPhotos->isNotEmpty())
    @component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents, 'wide' => true])
        <div class="velocity-gallery-grid">
            @foreach($velocityGalleryPhotos as $velocityGalleryPhoto)
                <a class="velocity-gallery-grid__item" href="{{ route('fileView', ['path' => 'topics/'.$velocityGalleryPhoto['file']]) }}" data-velocity-lightbox data-gallery="velocity-landing-gallery-{{ $TopicBlock->id }}" data-title="{{ $velocityGalleryPhoto['title'] }}">
                    <img src="{{ route('fileView', ['path' => 'topics/'.$velocityGalleryPhoto['file']]) }}?w=700&h=700&r=fit" alt="{{ $velocityGalleryPhoto['title'] }}" width="700" height="700" loading="lazy" decoding="async">
                </a>
            @endforeach
        </div>
        @if($TopicBlock->more_btn_status)
            @slot('actions')
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            @endslot
        @endif
    @endcomponent
@endif
