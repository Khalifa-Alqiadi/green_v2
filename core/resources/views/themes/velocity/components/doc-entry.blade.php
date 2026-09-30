@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityTitle = $Topic->{'title_'.$velocityLanguageCode} ?: $Topic->{'title_'.$velocityDefaultCode};
    $velocityDetailsField = filled($Topic->{'details_'.$velocityLanguageCode})
        ? 'details_'.$velocityLanguageCode
        : 'details_'.$velocityDefaultCode;
@endphp
<article class="velocity-doc-entry" id="docs-topic-{{ $Topic->id }}">
    <h3>
        @if($Topic->icon)
            <i class="{{ $Topic->icon }}" aria-hidden="true"></i>
        @endif
        {{ $velocityTitle }}
    </h3>
    @include(theme_view('components.listing-fields'), [
        'topic' => $Topic,
        'fields' => $Topic->webmasterSection->customFields ?? [],
    ])
    <div class="velocity-richtext">
        @if($Topic->webmasterSection->editor_status)
            {!! str_replace('"#', '"'.Request::url().'#', $Topic->{$velocityDetailsField}) !!}
        @else
            {!! nl2br(e($Topic->{$velocityDetailsField})) !!}
        @endif
    </div>
</article>
