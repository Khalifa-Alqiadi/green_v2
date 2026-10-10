@php
    $relatedVideos = Helper::relatedTopics($Topic->id, $WebmasterSection->id, 6);
@endphp
@if($relatedVideos->isNotEmpty())
    <aside class="yh-video-related" aria-labelledby="related-videos-title">
        <div class="yh-video-related__heading">
            <span aria-hidden="true"><i class="fa-solid fa-play"></i></span>
            <h2 id="related-videos-title">{{ __('frontend.relatedVideos') }}</h2>
        </div>
        <div class="yh-video-related__list">
            @foreach($relatedVideos as $relatedVideo)
                @php
                    $relatedTitle = $relatedVideo->$title_var ?: $relatedVideo->$title_var2;
                    $relatedUrl = Helper::topicURL($relatedVideo->id, '', $relatedVideo);
                    $relatedThumb = '';
                    if ((int) $relatedVideo->video_type === 1 && $relatedVideo->video_file) {
                        $relatedYoutubeId = Helper::Get_youtube_video_id($relatedVideo->video_file);
                        if ($relatedYoutubeId) {
                            $relatedThumb = 'https://img.youtube.com/vi/'.$relatedYoutubeId.'/mqdefault.jpg';
                        }
                    }
                    if (!$relatedThumb && $relatedVideo->photo_file) {
                        $relatedThumb = route('fileView', ['path' => 'topics/'.$relatedVideo->photo_file]);
                    }
                @endphp
                <a class="yh-video-related__item" href="{{ $relatedUrl }}">
                    <span class="yh-video-related__media">
                        @if($relatedThumb)
                            <img src="{{ $relatedThumb }}" alt="{{ Helper::topicPhotoAlt($relatedVideo) }}" loading="lazy" decoding="async">
                        @else
                            <i class="fa-regular fa-image" aria-hidden="true"></i>
                        @endif
                        <b aria-hidden="true"><i class="fa-solid fa-play"></i></b>
                    </span>
                    <strong>{{ $relatedTitle }}</strong>
                </a>
            @endforeach
        </div>
    </aside>
@endif
