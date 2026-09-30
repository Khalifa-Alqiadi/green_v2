@php
    $readAlsoTopics = collect([$previousTopic ?? null, $nextTopic ?? null])
        ->filter()
        ->unique('id')
        ->values();
@endphp

@if($readAlsoTopics->isNotEmpty())
    <section class="topic-read-also my-5" aria-labelledby="topic-read-also-title">
        <div class="topic-read-also-heading">
            <span class="topic-read-also-heading__icon" aria-hidden="true"><i class="fa-regular fa-bookmark"></i></span>
            <h2 id="topic-read-also-title">{{ __('frontend.readAlso') }}</h2>
            <span class="topic-read-also-heading__line" aria-hidden="true"></span>
        </div>

        <div class="row g-3">
            @foreach($readAlsoTopics as $readAlsoTopic)
                @php
                    $readAlsoTitle = $readAlsoTopic->{$title_var} ?: $readAlsoTopic->{$title_var2};
                    $readAlsoUrl = Helper::topicURL($readAlsoTopic->id, '', $readAlsoTopic);
                @endphp
                <div class="col-md-6">
                    <a href="{{ $readAlsoUrl }}" class="topic-read-also-card">
                        <span class="topic-read-also-card__media">
                            @if(filled($readAlsoTopic->photo_file))
                                <img src="{{ route('fileView', ['path' => 'topics/'.$readAlsoTopic->photo_file]) }}?w=220&h=160&r=fit"
                                     alt="{{ Helper::topicPhotoAlt($readAlsoTopic) }}"
                                     width="220" height="160" loading="lazy" decoding="async">
                            @else
                                <span class="topic-read-also-card__placeholder" aria-hidden="true">
                                    <i class="fa-regular fa-newspaper"></i>
                                </span>
                            @endif
                            <span class="topic-read-also-card__number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        </span>
                        <span class="topic-read-also-card__content">
                            <small>{{ __('frontend.readAlso') }}</small>
                            <strong>{{ $readAlsoTitle }}</strong>
                            <span class="topic-read-also-card__action">
                                {{ __('frontend.readMore') }}
                                <i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
                            </span>
                        </span>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif
