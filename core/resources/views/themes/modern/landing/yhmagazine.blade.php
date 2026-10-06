@php
    extract(require resource_path('views/themes/modern/landing/_yh-data.php'));
@endphp

@if($yhTopics->isNotEmpty())
    @php($latestIssue = $yhTopics->first())
    <section id="landing-block-{{ $TopicBlock->id }}" class="yh-home-section yh-magazine {{ $TopicBlock->css_classes }}">
        <div class="container">
            @include(theme_view('landing._yh-heading'), ['yhEyebrow' => __('yemen_home.magazine_eyebrow')])
            <div class="yh-magazine__layout meu-stagger">
                <a class="yh-magazine__featured" href="{{ Helper::topicURL($latestIssue->id, '', $latestIssue) }}">
                    <figure>
                        @if($latestIssue->photo_file)
                            <img src="{{ route('fileView', ['path' => 'topics/'.$latestIssue->photo_file]) }}" alt="" loading="lazy" decoding="async">
                        @else
                            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                        @endif
                    </figure>
                    <div>
                        <span>{{ __('yemen_home.magazine_latest') }}</span>
                        <h3>{{ $yhTopicTitle($latestIssue) }}</h3>
                        @if($yhTopicDetails($latestIssue))
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($yhTopicDetails($latestIssue)), 145) }}</p>
                        @endif
                        <b>{{ __('yemen_home.magazine_read') }} <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i></b>
                    </div>
                </a>
                <div class="yh-magazine__issues">
                    @foreach($yhTopics->slice(1, 3) as $item)
                        <a href="{{ Helper::topicURL($item->id, '', $item) }}">
                            <figure>
                                @if($item->photo_file)
                                    <img src="{{ route('fileView', ['path' => 'topics/'.$item->photo_file]) }}" alt="" loading="lazy" decoding="async">
                                @else
                                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                                @endif
                            </figure>
                            <span>{{ __('yemen_home.magazine_archive') }}</span>
                            <h3>{{ $yhTopicTitle($item) }}</h3>
                            <b>{{ __('yemen_home.magazine_read') }} <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i></b>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
