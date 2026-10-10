@php
    $scholarTitle = $Topic->$title_var ?: $Topic->$title_var2;
    $scholarDetails = trim(strip_tags((string) ($Topic->$details_var ?: $Topic->$details_var2)));
    $scholarUrl = Helper::topicURL($Topic->id, '', $Topic);
    $scholarInitial = mb_substr($scholarTitle, 0, 1);
@endphp
<article class="yh-scholar-card">
    <a href="{{ $scholarUrl }}" class="yh-scholar-card__link" aria-label="{{ $scholarTitle }}">
        <span class="yh-scholar-card__portrait">
            @if($Topic->photo_file)
                <img src="{{ route('fileView', ['path' => 'topics/'.$Topic->photo_file]) }}" alt="{{ Helper::topicPhotoAlt($Topic) }}" loading="lazy" decoding="async">
            @else
                <b aria-hidden="true">{{ $scholarInitial }}</b>
            @endif
        </span>
        <span class="yh-scholar-card__body">
            <small>{{ __('yemen_home.scholars_biography') }}</small>
            <strong>{{ $scholarTitle }}</strong>
            @if($scholarDetails)
                <span class="yh-scholar-card__excerpt">{!! str_replace('"', " ", \Illuminate\Support\Str::limit($scholarDetails, 115)) !!}</span>
            @endif
            <span class="yh-scholar-card__action">{{ __('yemen_home.scholars_view_profile') }} <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i></span>
        </span>
    </a>
</article>
