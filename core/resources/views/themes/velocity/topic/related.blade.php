@if($WebmasterSection->related_status)
    @php
        $velocityRelatedTopics = collect($Topic->relatedTopics)->map(fn ($item) => $item->topic)->filter();
    @endphp
    @if($velocityRelatedTopics->isNotEmpty())
        <section class="velocity-topic-section" id="related" aria-labelledby="velocity-related-title">
            <h2 id="velocity-related-title">{{ __('backend.relatedTopics') }}</h2>
            <div class="velocity-topic-grid velocity-topic-grid--related">
                @foreach($velocityRelatedTopics as $Post)
                    @include(theme_view('topic.card2'), ['Post' => $Post])
                @endforeach
            </div>
        </section>
    @endif
@endif
