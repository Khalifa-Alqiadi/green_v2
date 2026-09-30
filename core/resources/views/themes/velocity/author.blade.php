@extends(theme_view('layouts.master'))

@php
    $authorLanguage = Helper::currentLanguage()->code;
    $authorName = $Author->{'name_'.$authorLanguage} ?: $Author->name_ar;
    $authorPosition = $Author->{'position_'.$authorLanguage} ?: $Author->position_ar;
@endphp

@section('content')
    @include(theme_view('components.page-heading'), [
        'title' => $authorName,
        'breadcrumbs' => [['label' => __('backend.authors')], ['label' => $authorName]],
        'eyebrow' => __('backend.authors'),
        'variant' => 'compact',
    ])

    <section class="velocity-section velocity-author-page" id="content">
        <div class="velocity-container">
            <div class="velocity-author-profile">
                <div class="velocity-author-profile__portrait">
                    @if($Author->photo)
                        <img src="{{ route('fileView', ['path' => 'authors/'.$Author->photo]) }}"
                             alt="{{ $authorName }}" width="220" height="220" loading="eager" decoding="async">
                    @else
                        <span aria-hidden="true">{{ mb_substr($authorName, 0, 1) }}</span>
                    @endif
                </div>
                <div class="velocity-author-profile__details">
                    <span class="velocity-author-profile__eyebrow">{{ __('backend.authors') }}</span>
                    <h2>{{ $authorName }}</h2>
                    @if($authorPosition)
                        <p>{{ $authorPosition }}</p>
                    @endif
                </div>
                <div class="velocity-author-profile__count">
                    <strong>{{ $Topics->total() }}</strong>
                    <span>{{ __('frontend.authorWorks') }}</span>
                </div>
            </div>

            <div class="velocity-author-page__heading">
                <h2>{{ __('frontend.authorWorks') }}</h2>
                <span aria-hidden="true"></span>
            </div>

            @if($Topics->isEmpty())
                @include(theme_view('components.empty-state'))
            @else
                <div class="velocity-topic-grid velocity-topic-grid--editorial">
                    @foreach($Topics as $topic)
                        @include(theme_view('components.topic-card'), ['topic' => $topic, 'showMeta' => true])
                    @endforeach
                </div>
                {{ $Topics->links(theme_view('components.pagination')) }}
            @endif
        </div>
    </section>
@endsection
