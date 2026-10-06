@php
    extract(require resource_path('views/themes/modern/landing/_yh-data.php'));
@endphp

@if($yhTopics->isNotEmpty())
    <section id="landing-block-{{ $TopicBlock->id }}" class="yh-home-section yh-books {{ $TopicBlock->css_classes }}">
        <div class="container">
            @include(theme_view('landing._yh-heading'), ['yhEyebrow' => __('yemen_home.books_eyebrow')])
            <div class="yh-books__grid meu-stagger">
                @foreach($yhTopics as $item)
                    <a class="yh-book" href="{{ Helper::topicURL($item->id, '', $item) }}">
                        <span class="yh-book__number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="yh-book__cover">
                            @if($item->photo_file)
                                <img src="{{ route('fileView', ['path' => 'topics/'.$item->photo_file]) }}" alt="" loading="lazy" decoding="async">
                            @else
                                <span class="yh-book__placeholder" aria-hidden="true"><i class="fa-solid fa-book-open"></i></span>
                            @endif
                        </div>
                        <div class="yh-book__body">
                            <small>{{ __('yemen_home.books_library') }}</small>
                            <h3>{{ $yhTopicTitle($item) }}</h3>
                            <span class="yh-book__meta"><span>{{ __('yemen_home.books_explore') }}</span><i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i></span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
