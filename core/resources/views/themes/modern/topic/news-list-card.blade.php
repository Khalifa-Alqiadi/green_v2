@php
    $newsTitle = $Topic->$title_var ?: $Topic->$title_var2;
    $newsDetails = strip_tags($Topic->$details_var ?: $Topic->$details_var2 ?: '');
    $newsPhoto = $Topic->photo_file ?: optional($Topic->photos->first())->file;
    $newsUrl = Helper::topicURL($Topic->id, '', $Topic);
@endphp
<article class="yh-news-list__item">
    <a class="yh-news-list__image" href="{{ $newsUrl }}" aria-label="{{ $newsTitle }}">
        @if($newsPhoto)
            <img src="{{ route('fileView', ['path' => 'topics/'.$newsPhoto]) }}?w=560&h=360"
                 alt="{{ Helper::topicPhotoAlt($Topic) }}" loading="lazy" decoding="async">
        @else
            <span class="yh-news-list__placeholder" aria-hidden="true"><i class="fa-regular fa-image"></i></span>
        @endif
    </a>
    <div class="yh-news-list__body">
        <time class="yh-news-list__date" datetime="{{ $Topic->date }}">{{ $Topic->date }}</time>
        <h2><a href="{{ $newsUrl }}">{{ $newsTitle }}</a></h2>
        @if($newsDetails)
            <p>{{ \Illuminate\Support\Str::limit($newsDetails, 170) }}</p>
        @endif
        {{-- <a class="yh-news-list__read" href="{{ $newsUrl }}">{{ __('frontend.moreDetails') }}
            <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i>
        </a> --}}
    </div>
</article>
