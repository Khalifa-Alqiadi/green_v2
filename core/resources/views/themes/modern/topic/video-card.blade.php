@php
    $videoTitle = $Topic->$title_var ?: $Topic->$title_var2;
    $videoUrl = Helper::topicURL($Topic->id, '', $Topic);
    $videoThumb = '';
    if ((int) $Topic->video_type === 1 && $Topic->video_file) {
        $youtubeId = Helper::Get_youtube_video_id($Topic->video_file);
        if ($youtubeId) {
            $videoThumb = 'https://img.youtube.com/vi/'.$youtubeId.'/hqdefault.jpg';
        }
    }
    if (!$videoThumb && $Topic->photo_file) {
        $videoThumb = route('fileView', ['path' => 'topics/'.$Topic->photo_file]);
    }
    if (!$videoThumb && $Topic->photos->isNotEmpty()) {
        $videoThumb = route('fileView', ['path' => 'topics/'.$Topic->photos->first()->file]);
    }
@endphp
<article class="yh-video-card">
    <a href="{{ $videoUrl }}" class="yh-video-card__link" aria-label="{{ $videoTitle }}">
        <span class="yh-video-card__media">
            @if($videoThumb)
                <img src="{{ $videoThumb }}" alt="{{ Helper::topicPhotoAlt($Topic) }}" loading="lazy" decoding="async">
            @else
                <span class="yh-video-card__placeholder" aria-hidden="true"><i class="fa-regular fa-image"></i></span>
            @endif
            <span class="yh-video-card__play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
        </span>
        <span class="yh-video-card__body">
            <strong>{{ $videoTitle }}</strong>
            <span class="yh-video-card__watch">{{ __('frontend.watchVideo') }} <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i></span>
        </span>
    </a>
</article>
