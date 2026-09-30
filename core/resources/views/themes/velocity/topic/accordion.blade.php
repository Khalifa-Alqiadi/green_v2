@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityTitle = $Topic->{'title_'.$velocityLanguageCode} ?: $Topic->{'title_'.$velocityDefaultCode};
    $velocityDetailsField = filled($Topic->{'details_'.$velocityLanguageCode})
        ? 'details_'.$velocityLanguageCode
        : 'details_'.$velocityDefaultCode;
    $velocityDetails = (string) $Topic->{$velocityDetailsField};
@endphp
<details class="velocity-faq velocity-panel" id="faq-{{ (int) ($CatId ?? 0) }}-{{ $Topic->id }}" data-velocity-disclosure data-velocity-reveal>
    <summary>
        <span class="velocity-faq__label">
            @if($Topic->icon)
                <i class="{{ $Topic->icon }}" aria-hidden="true"></i>
            @endif
            {{ $velocityTitle }}
        </span>
        <b class="velocity-faq__toggle" aria-hidden="true">+</b>
    </summary>
    <div class="velocity-rich-content velocity-richtext">
        @if($Topic->webmasterSection->editor_status)
            {!! str_replace('"#', '"'.Request::url().'#', $velocityDetails) !!}
        @else
            {!! nl2br(e($velocityDetails)) !!}
        @endif

        @include(theme_view('components.listing-fields'), [
            'topic' => $Topic,
            'fields' => $Topic->webmasterSection->customFields ?? [],
        ])

        @if(filled($Topic->audio_file))
            @if((int) $Topic->video_type === 3)
                <div class="velocity-embed">{!! $Topic->audio_file !!}</div>
            @else
                <div class="velocity-audio-player" data-velocity-audio-player data-play-label="{{ __('frontend.play') }}" data-pause-label="{{ __('frontend.pause') }}">
                    <audio controls preload="none" src="{{ route('fileView', ['path' => 'topics/'.$Topic->audio_file]) }}">
                        {{ $velocityTitle }}
                    </audio>
                </div>
            @endif
        @endif
    </div>
</details>
