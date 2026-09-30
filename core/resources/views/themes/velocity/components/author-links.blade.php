@php
    $authorLanguage = Helper::currentLanguage()->code;
    $authorDefaultLanguage = config('smartend.default_language');
@endphp
@if(Helper::GeneralWebmasterSettings('authors_status') && collect($authors)->isNotEmpty())
    <span class="velocity-author-links {{ $class ?? '' }}">
        <span class="velocity-author-links__label">{{ __('backend.authors') }}</span>
        @foreach($authors as $author)
            <a href="{{ $authorLanguage === $authorDefaultLanguage ? route('authorProfile', ['author' => $author->id]) : route('authorProfileLocalized', ['lang' => $authorLanguage, 'author' => $author->id]) }}">
                {{ $author->{'name_'.$authorLanguage} ?: $author->name_ar }}
            </a>
        @endforeach
    </span>
@endif
