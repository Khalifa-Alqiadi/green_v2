@php
    $velocityLanguageCode = @Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityTitle = (string) ($topic->{'title_'.$velocityLanguageCode} ?: $topic->{'title_'.$velocityDefaultCode});
    $velocityDetailsField = filled($topic->{'details_'.$velocityLanguageCode} ?? null)
        ? 'details_'.$velocityLanguageCode
        : 'details_'.$velocityDefaultCode;
    $velocitySummary = trim(strip_tags((string) ($topic->{$velocityDetailsField} ?? '')));
    $velocityUrl = Helper::topicURL($topic->id, '', $topic);
    $velocityImage = filled($topic->photo_file ?? null) ? $topic->photo_file : optional($topic->photos->first())->file;
    $velocitySectionType = (int) ($topic->webmasterSection->type ?? 0);
    $velocityImageWidth = ($featured ?? false) ? 900 : 520;
    $velocityImageHeight = ($featured ?? false) ? 600 : 360;
    $velocityImageLoading = $loading ?? 'lazy';
    $velocityImageUrl = $velocityImage
        ? route('fileView', ['path' => 'topics/'.$velocityImage])
        : null;
    $velocityIsVideo = $velocitySectionType === 2 && filled($topic->video_file ?? null);
    $velocityIsAudio = $velocitySectionType === 3 && filled($topic->audio_file ?? null);
    $velocityIsAudioEmbed = $velocityIsAudio && (int) ($topic->video_type ?? 0) === 3;
    $velocityAudioExtension = strtolower(pathinfo((string) ($topic->audio_file ?? ''), PATHINFO_EXTENSION));
    $velocityAudioMime = $velocityAudioExtension === 'wav' ? 'audio/wav' : 'audio/mpeg';
    $velocityYoutubeId = $velocityIsVideo && (int) ($topic->video_type ?? 0) === 1
        ? Helper::Get_youtube_video_id($topic->video_file)
        : null;
    $velocityPreviewUrl = $velocityImageUrl ?: ($velocityYoutubeId
        ? 'https://i.ytimg.com/vi/'.rawurlencode($velocityYoutubeId).'/hqdefault.jpg'
        : null);
    $velocitySrcset = $velocityImageUrl
        ? $velocityImageUrl.'?w=320&h=220&r=fit 320w, '.$velocityImageUrl.'?w=520&h=360&r=fit 520w'
        : null;
    if ($velocitySrcset && ($featured ?? false)) {
        $velocitySrcset .= ', '.$velocityImageUrl.'?w=900&h=600&r=fit 900w';
    }
    $velocityVariant = in_array(($variant ?? ''), ['editorial', 'service', 'compact', 'featured', 'news', 'listing-featured'], true)
        ? $variant
        : (((bool) ($topic->webmasterSection->date_status ?? false)) ? 'editorial' : 'service');
    $velocityShowMeta = $showMeta ?? ($velocityVariant === 'editorial');
    $velocityCategories = collect($topic->categories ?? [])->pluck('section')->filter()->take(2);
    $velocityAuthors = $topic->authors->where('status', true);
@endphp
<article
    class="velocity-topic-card velocity-topic-card--{{ $velocityVariant }} {{ ($featured ?? false) ? 'velocity-topic-card--featured' : '' }} {{ ($compact ?? false) ? 'velocity-topic-card--compact' : '' }}"
    data-velocity-topic-card
    data-velocity-reveal
>
    @if($velocityPreviewUrl || $velocityIsVideo)
        <a class="velocity-topic-card__media" href="{{ $velocityUrl }}" aria-label="{{ $velocityTitle }}">
            @if($velocityPreviewUrl)
                <img
                    src="{{ $velocityImageUrl ? $velocityImageUrl.'?w='.$velocityImageWidth.'&h='.$velocityImageHeight.'&r=fit' : $velocityPreviewUrl }}"
                    @if($velocitySrcset) srcset="{{ $velocitySrcset }}" @endif
                    sizes="{{ ($featured ?? false) ? '(max-width: 48rem) calc(100vw - 2rem), 60vw' : '(max-width: 40rem) calc(100vw - 2rem), (max-width: 75rem) 45vw, 24rem' }}"
                    alt="{{ Helper::topicPhotoAlt($topic) }}"
                    width="{{ $velocityImageWidth }}"
                    height="{{ $velocityImageHeight }}"
                    loading="{{ $velocityImageLoading }}"
                    decoding="async"
                    @if(!$velocityImageUrl) referrerpolicy="no-referrer" @endif
                    @if($velocityImageLoading === 'eager') fetchpriority="high" @endif
                >
            @else
                <span class="velocity-topic-card__media-placeholder" aria-hidden="true"></span>
            @endif
            @if(in_array($velocitySectionType, [2, 3], true))
                <span class="velocity-topic-card__media-mark" aria-hidden="true">{{ $velocitySectionType === 2 ? '▶' : '♪' }}</span>
            @elseif(($topic->photos->count() ?? 0) > 1)
                <span class="velocity-topic-card__media-count">{{ $topic->photos->count() }}</span>
            @endif
        </a>
    @endif
    @if($velocityIsAudio)
        <div class="velocity-topic-card__audio">
            @if($velocityIsAudioEmbed)
                <div class="velocity-embed">{!! $topic->audio_file !!}</div>
            @else
                <div
                    class="velocity-audio-player"
                    data-velocity-audio-player
                    data-play-label="{{ __('frontend.play') }}"
                    data-pause-label="{{ __('frontend.pause') }}"
                >
                    <audio controls preload="none">
                        <source src="{{ route('fileView', ['path' => 'topics/'.$topic->audio_file]) }}" type="{{ $velocityAudioMime }}">
                        <a href="{{ route('fileView', ['path' => 'topics/'.$topic->audio_file]) }}">{{ __('frontend.downloadAttach') }}</a>
                    </audio>
                </div>
            @endif
        </div>
    @endif
    <div class="velocity-topic-card__body">
        @if($velocityShowMeta && (filled($topic->date ?? null) || $velocityCategories->isNotEmpty()))
            <div class="velocity-topic-card__meta">
                @if($velocityCategories->isNotEmpty())
                    <span>{{ $velocityCategories->map(fn ($category) => $category->{'title_'.$velocityLanguageCode} ?: $category->{'title_'.$velocityDefaultCode})->filter()->join(' · ') }}</span>
                @endif
                @if(filled($topic->date ?? null))
                    <time datetime="{{ $topic->date }}">{{ Helper::formatDate($topic->date) }}</time>
                @endif
            </div>
        @endif
        @if(filled($kicker ?? null))
            <p class="velocity-topic-card__kicker">{{ $kicker }}</p>
        @endif
        <h3 class="velocity-topic-card__title">
            <a href="{{ $velocityUrl }}">{{ $velocityTitle }}</a>
        </h3>
        @if($velocityAuthors->isNotEmpty())
            @include(theme_view('components.author-links'), ['authors' => $velocityAuthors, 'class' => 'velocity-topic-card__authors'])
        @endif
        @if($velocitySummary !== '')
            <p class="velocity-topic-card__summary">{{ \Illuminate\Support\Str::limit($velocitySummary, ($featured ?? false) ? 240 : 150) }}</p>
        @endif
        @if($showFields ?? false)
            @include(theme_view('components.listing-fields'), [
                'topic' => $topic,
                'fields' => $topic->webmasterSection->customFields ?? [],
            ])
        @endif
        <a class="velocity-topic-card__link" href="{{ $velocityUrl }}">
            {{ __('frontend.moreDetails') }} <span class="velocity-arrow" aria-hidden="true">→</span>
        </a>
    </div>
</article>
