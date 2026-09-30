@extends(theme_view('layouts.master'))

@php
    $authorLanguage = Helper::currentLanguage()->code;
    $authorDefaultLanguage = config('smartend.default_language');
    $authorName = $Author->{'name_'.$authorLanguage} ?: $Author->name_ar;
    $authorPosition = $Author->{'position_'.$authorLanguage} ?: $Author->position_ar;
@endphp

@section('content')
    <section class="breadcrumbs">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>{{ $authorName }}</h1>
                <ol>
                    <li><a href="{{ Helper::homeURL() }}">{{ __('backend.home') }}</a></li>
                    <li class="active">{{ $authorName }}</li>
                </ol>
            </div>
        </div>
    </section>

    <section class="modern-author-page" id="content">
        <div class="container">
            <div class="modern-author-profile">
                <div class="modern-author-profile__portrait">
                    @if($Author->photo)
                        <img src="{{ route('fileView', ['path' => 'authors/'.$Author->photo]) }}"
                             alt="{{ $authorName }}" width="180" height="180" loading="eager" decoding="async">
                    @else
                        <span aria-hidden="true">{{ mb_substr($authorName, 0, 1) }}</span>
                    @endif
                </div>
                <div class="modern-author-profile__copy">
                    <span class="modern-author-profile__eyebrow">{{ __('backend.authors') }}</span>
                    <h2>{{ $authorName }}</h2>
                    @if($authorPosition)
                        <p>{{ $authorPosition }}</p>
                    @endif
                </div>
                <div class="modern-author-profile__count">
                    <strong>{{ $Topics->total() }}</strong>
                    <span>{{ __('frontend.authorWorks') }}</span>
                </div>
            </div>

            <div class="modern-author-page__section-title">
                <h2>{{ __('frontend.authorWorks') }}</h2>
                <span aria-hidden="true"></span>
            </div>

            @if($Topics->isEmpty())
                <div class="card text-center p-5 no-data">{{ __('frontend.noData') }}</div>
            @else
                <div class="row g-4">
                    @foreach($Topics as $authorTopic)
                        @php
                            $title = $authorTopic->{'title_'.$authorLanguage} ?: $authorTopic->{'title_'.$authorDefaultLanguage};
                            $details = filled($authorTopic->{'details_'.$authorLanguage}) ? 'details_'.$authorLanguage : 'details_'.$authorDefaultLanguage;
                            $topic_link_url = Helper::topicURL($authorTopic->id, '', $authorTopic);
                        @endphp
                        <div class="col-lg-4 col-md-6">
                            @include(theme_view('topic.card'), ['Topic' => $authorTopic])
                        </div>
                    @endforeach
                </div>
                <div class="modern-author-page__pagination">
                    {{ $Topics->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </section>
@endsection
