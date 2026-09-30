@if(!empty($Post))
    @php
        $velocityCard2Language = @Helper::currentLanguage()->code;
        $velocityCard2DefaultLanguage = config('smartend.default_language');
        $velocityCard2Title = (string) ($Post->{'title_'.$velocityCard2Language} ?: $Post->{'title_'.$velocityCard2DefaultLanguage});
        $velocityCard2Url = Helper::topicURL($Post->id, '', $Post);
        $velocityCard2Image = filled($Post->photo_file ?? null)
            ? $Post->photo_file
            : optional($Post->photos->first())->file;
        $velocityCard2ImageUrl = $velocityCard2Image
            ? route('fileView', ['path' => 'topics/'.$velocityCard2Image])
            : null;
        $velocityCard2Type = (int) ($Post->webmasterSection->type ?? 0);
        $velocityCard2IsVideo = $velocityCard2Type === 2 && filled($Post->video_file ?? null);
        $velocityCard2YoutubeId = $velocityCard2IsVideo && (int) ($Post->video_type ?? 0) === 1
            ? Helper::Get_youtube_video_id($Post->video_file)
            : null;
        $velocityCard2PreviewUrl = $velocityCard2ImageUrl ?: ($velocityCard2YoutubeId
            ? 'https://i.ytimg.com/vi/'.rawurlencode($velocityCard2YoutubeId).'/hqdefault.jpg'
            : null);
        $velocityCard2PhotoCount = $Post->photos->count() ?? 0;
        $velocityCard2TitleId = 'velocity-topic-card2-title-'.$Post->id;
    @endphp

    <article
        class="velocity-topic-card2"
        aria-labelledby="{{ $velocityCard2TitleId }}"
        data-velocity-topic-card
        data-velocity-reveal
    >
        <a
            class="velocity-topic-card2__media"
            href="{{ $velocityCard2Url }}"
            tabindex="-1"
            aria-hidden="true"
        >
            @if($velocityCard2PreviewUrl)
                <img
                    src="{{ $velocityCard2ImageUrl ? $velocityCard2ImageUrl.'?w=240&h=200&r=fit' : $velocityCard2PreviewUrl }}"
                    @if($velocityCard2ImageUrl) srcset="{{ $velocityCard2ImageUrl }}?w=160&h=140&r=fit 160w, {{ $velocityCard2ImageUrl }}?w=240&h=200&r=fit 240w" @endif
                    sizes="(max-width: 30rem) 5.25rem, 6.75rem"
                    alt="{{ Helper::topicPhotoAlt($Post) }}"
                    width="240"
                    height="200"
                    loading="lazy"
                    decoding="async"
                    @if(!$velocityCard2ImageUrl) referrerpolicy="no-referrer" @endif
                >
            @else
                <span class="velocity-topic-card2__placeholder">
                    @include(theme_view('components.icon'), ['name' => 'image'])
                </span>
            @endif

            @if(in_array($velocityCard2Type, [2, 3], true))
                <span class="velocity-topic-card2__media-mark" aria-hidden="true">{{ $velocityCard2Type === 2 ? '▶' : '♪' }}</span>
            @elseif($velocityCard2PhotoCount > 1)
                <span class="velocity-topic-card2__media-mark" aria-hidden="true">{{ $velocityCard2PhotoCount }}</span>
            @endif
        </a>

        <div class="velocity-topic-card2__body">
            <div class="velocity-topic-card2__content">
                <h3 class="velocity-topic-card2__title" id="{{ $velocityCard2TitleId }}">
                    <a href="{{ $velocityCard2Url }}">{{ $velocityCard2Title }}</a>
                </h3>

                @if(filled($Post->date ?? null))
                    <time class="velocity-topic-card2__date" datetime="{{ $Post->date }}">
                        {{ Helper::formatDate($Post->date) }}
                    </time>
                @endif
            </div>

            <a
                class="velocity-topic-card2__action"
                href="{{ $velocityCard2Url }}"
                aria-label="{{ __('frontend.moreDetails') }}: {{ $velocityCard2Title }}"
            >
                <span class="velocity-arrow" aria-hidden="true">→</span>
            </a>
        </div>
    </article>
@endif
