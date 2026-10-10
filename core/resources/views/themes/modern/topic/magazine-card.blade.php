@php
    $issueTitle = $Topic->$title_var ?: $Topic->$title_var2;
    $issueUrl = Helper::topicURL($Topic->id, '', $Topic);
    $issuePhoto = $Topic->photo_file ?: optional($Topic->photos->first())->file;
@endphp
<article class="yh-issue-card">
    <a class="yh-issue-card__link" href="{{ $issueUrl }}" aria-label="{{ $issueTitle }}">
        <span class="yh-issue-card__cover">
            @if($issuePhoto)
                <img src="{{ route('fileView', ['path' => 'topics/'.$issuePhoto]) }}" alt="{{ Helper::topicPhotoAlt($Topic) }}" loading="lazy" decoding="async">
            @else
                <span class="yh-issue-card__placeholder" aria-hidden="true"><i class="fa-solid fa-book-open"></i></span>
            @endif
        </span>
        <span class="yh-issue-card__info">
            <small>{{ __('yemen_home.magazine_archive') }}@if($Topic->date) <span aria-hidden="true">·</span> {{ Helper::formatDate($Topic->date) }}@endif</small>
            <strong>{{ $issueTitle }}</strong>
            <span class="yh-issue-card__action">{{ __('yemen_home.magazine_read') }} <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i></span>
        </span>
    </a>
</article>
