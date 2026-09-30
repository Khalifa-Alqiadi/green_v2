@php
    $velocityFieldValue = trim((string) ($value ?? ''));
    $velocityFieldType = (int) $field->type;
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityOptions = preg_split(
        '/\r\n|[\r\n]/',
        (string) ($field->{'details_'.$velocityLanguageCode} ?: $field->{'details_'.$velocityDefaultCode})
    );
    $velocityOptionValues = in_array($velocityFieldType, [7, 17], true)
        ? array_filter(array_map('trim', explode(',', $velocityFieldValue)))
        : [$velocityFieldValue];
    $velocityOptionLabels = collect($velocityOptionValues)
        ->map(fn ($option) => $velocityOptions[((int) $option) - 1] ?? null)
        ->filter();
@endphp

@if($velocityFieldValue === '')
    <span aria-label="{{ __('frontend.noData') }}">—</span>
@elseif(in_array($velocityFieldType, [6, 7, 13, 17], true))
    <span class="velocity-chip-list">
        @foreach($velocityOptionLabels as $velocityOptionLabel)
            @php
                $velocityOptionIndex = array_search($velocityOptionLabel, $velocityOptions, true);
                $velocityOptionColor = trim((string) (preg_split('/\r\n|[\r\n]/', (string) $field->colors)[$velocityOptionIndex] ?? ''));
                $velocityOptionColor = preg_match('/^(#[0-9a-f]{3,8}|[a-z]+)$/i', $velocityOptionColor)
                    ? $velocityOptionColor
                    : null;
            @endphp
            <span class="velocity-chip" @if($velocityOptionColor) style="--velocity-chip-color: {{ $velocityOptionColor }}" @endif>
                {{ $velocityOptionLabel }}
            </span>
        @endforeach
        @if($velocityOptionLabels->isEmpty())
            <span>{{ $velocityFieldValue }}</span>
        @endif
    </span>
@elseif($velocityFieldType === 8)
    <a href="{{ route('fileView', ['path' => 'topics/'.$velocityFieldValue]) }}" target="_blank" rel="noopener" @if($detail ?? false) data-velocity-lightbox data-gallery="velocity-field-{{ $field->id }}" data-title="{{ $label ?? '' }}" @endif>
        <img
            class="{{ ($detail ?? false) ? 'velocity-field-media velocity-field-media--image' : 'velocity-table-thumbnail' }}"
            src="{{ route('fileView', ['path' => 'topics/'.$velocityFieldValue]) }}?w={{ ($detail ?? false) ? 960 : 160 }}&h={{ ($detail ?? false) ? 640 : 120 }}&r=fit"
            alt="{{ $label ?? '' }}"
            width="{{ ($detail ?? false) ? 960 : 80 }}"
            height="{{ ($detail ?? false) ? 640 : 60 }}"
            loading="lazy"
            decoding="async"
        >
    </a>
@elseif($velocityFieldType === 9)
    @php
        $velocityExtension = strtolower(pathinfo($velocityFieldValue, PATHINFO_EXTENSION));
    @endphp
    @if(($detail ?? false) && in_array($velocityExtension, ['mp3', 'mpga', 'wav', 'ogg'], true))
        <div class="velocity-field-media velocity-audio-player" data-velocity-audio-player data-play-label="{{ __('frontend.play') }}" data-pause-label="{{ __('frontend.pause') }}">
            <audio controls preload="none">
                <source src="{{ route('fileView', ['path' => 'topics/'.$velocityFieldValue]) }}">
                <a href="{{ route('fileView', ['path' => 'topics/'.$velocityFieldValue]) }}" target="_blank" rel="noopener">{{ basename($velocityFieldValue) }}</a>
            </audio>
        </div>
    @else
        <a href="{{ route('fileView', ['path' => 'topics/'.$velocityFieldValue]) }}" target="_blank" rel="noopener">
            {{ basename($velocityFieldValue) }}
        </a>
    @endif
@elseif($velocityFieldType === 10)
    @if($detail ?? false)
        <video class="velocity-field-media" controls preload="none" data-velocity-video-player>
            <source src="{{ route('fileView', ['path' => 'topics/'.$velocityFieldValue]) }}">
            <a href="{{ route('fileView', ['path' => 'topics/'.$velocityFieldValue]) }}" target="_blank" rel="noopener">{{ basename($velocityFieldValue) }}</a>
        </video>
    @else
    <a href="{{ route('fileView', ['path' => 'topics/'.$velocityFieldValue]) }}" target="_blank" rel="noopener">
        {{ basename($velocityFieldValue) }}
    </a>
    @endif
@elseif($velocityFieldType === 11)
    @php
        $velocityVideoId = Helper::Get_youtube_video_id($velocityFieldValue);
    @endphp
    @if($velocityVideoId)
        @if($detail ?? false)
            <div class="velocity-field-media velocity-media-frame">
                <iframe src="https://www.youtube-nocookie.com/embed/{{ urlencode($velocityVideoId) }}" title="{{ filled($label ?? null) ? $label : __('backend.viewDetails') }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen></iframe>
            </div>
        @else
            <a href="https://www.youtube.com/watch?v={{ urlencode($velocityVideoId) }}" target="_blank" rel="noopener">
                {{ __('backend.viewDetails') }}
            </a>
        @endif
    @else
        <span>{{ $velocityFieldValue }}</span>
    @endif
@elseif($velocityFieldType === 12)
    @php
        $velocityVideoId = Helper::Get_vimeo_video_id($velocityFieldValue);
    @endphp
    @if($velocityVideoId)
        @if($detail ?? false)
            <div class="velocity-field-media velocity-media-frame">
                <iframe src="https://player.vimeo.com/video/{{ urlencode($velocityVideoId) }}?title=0&amp;byline=0" title="{{ filled($label ?? null) ? $label : __('backend.viewDetails') }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen; picture-in-picture" allowfullscreen></iframe>
            </div>
        @else
            <a href="https://vimeo.com/{{ urlencode($velocityVideoId) }}" target="_blank" rel="noopener">
                {{ __('backend.viewDetails') }}
            </a>
        @endif
    @else
        <span>{{ $velocityFieldValue }}</span>
    @endif
@elseif($velocityFieldType === 14)
    <span>{{ (int) $velocityFieldValue === 1 ? __('backend.yes') : __('backend.no') }}</span>
@elseif(in_array($velocityFieldType, [4, 5], true))
    <time datetime="{{ $velocityFieldValue }}">{{ Helper::formatDate($velocityFieldValue) }}</time>
@elseif($velocityFieldType === 16)
    <span>{{ trim(strip_tags($velocityFieldValue)) }}</span>
@elseif($velocityFieldType === 18)
    @php
        $velocityRelatedTopic = Helper::Topic((int) $velocityFieldValue);
    @endphp
    @if($velocityRelatedTopic)
        @php
            $velocityRelatedTitle = $velocityRelatedTopic->{'title_'.$velocityLanguageCode}
                ?: $velocityRelatedTopic->{'title_'.$velocityDefaultCode};
        @endphp
        <a href="{{ Helper::topicURL($velocityRelatedTopic->id, '', $velocityRelatedTopic) }}">{{ $velocityRelatedTitle }}</a>
    @else
        <span>{{ $velocityFieldValue }}</span>
    @endif
@elseif(filter_var($velocityFieldValue, FILTER_VALIDATE_URL))
    <a href="{{ $velocityFieldValue }}" target="_blank" rel="noopener">{{ $velocityFieldValue }}</a>
@else
    <span>{{ $velocityFieldValue }}</span>
@endif
