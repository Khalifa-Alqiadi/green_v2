@php
    $authorLanguage = Helper::currentLanguage()->code;
    $authorDefaultLanguage = config('smartend.default_language');
@endphp
@if(Helper::GeneralWebmasterSettings('authors_status') && collect($authors)->isNotEmpty())
    <div class="modern-author-links">
        <span class="modern-author-links__label"><i class="bi bi-person" aria-hidden="true"></i> {{ __('backend.authors') }}</span>
        @foreach($authors as $author)
            @if($linked ?? true)
                <a href="{{ $authorLanguage === $authorDefaultLanguage ? route('authorProfile', ['author' => $author->id]) : route('authorProfileLocalized', ['lang' => $authorLanguage, 'author' => $author->id]) }}">
                    {{ $author->{'name_'.$authorLanguage} ?: $author->name_ar }}
                </a>
            @else
                <span class="modern-author-links__name">{{ $author->{'name_'.$authorLanguage} ?: $author->name_ar }}</span>
            @endif
        @endforeach
    </div>
@endif
