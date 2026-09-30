@if(!empty($previousTopic) || !empty($nextTopic))
    @php
        $velocityLanguageCode = Helper::currentLanguage()->code;
        $velocityDefaultCode = config('smartend.default_language');
    @endphp
    <nav class="velocity-topic-navigation" aria-label="{{ __('backend.relatedTopics') }}">
        @if(!empty($previousTopic))
            <a class="velocity-topic-navigation__link velocity-topic-navigation__link--previous" rel="prev" href="{{ Helper::topicURL($previousTopic->id, '', $previousTopic) }}">
                <span class="velocity-topic-navigation__arrow" aria-hidden="true">
                    @include(theme_view('components.icon'), ['name' => 'arrow-left'])
                </span>
                <span class="velocity-topic-navigation__copy">
                    <small>{{ trim(str_replace('&laquo;', '', __('pagination.previous'))) }}</small>
                    <strong>{{ $previousTopic->{'title_'.$velocityLanguageCode} ?: $previousTopic->{'title_'.$velocityDefaultCode} }}</strong>
                </span>
            </a>
        @endif
        @if(!empty($nextTopic))
            <a class="velocity-topic-navigation__link velocity-topic-navigation__link--next" rel="next" href="{{ Helper::topicURL($nextTopic->id, '', $nextTopic) }}">
                <span class="velocity-topic-navigation__copy">
                    <small>{{ trim(str_replace('&raquo;', '', __('pagination.next'))) }}</small>
                    <strong>{{ $nextTopic->{'title_'.$velocityLanguageCode} ?: $nextTopic->{'title_'.$velocityDefaultCode} }}</strong>
                </span>
                <span class="velocity-topic-navigation__arrow" aria-hidden="true">
                    @include(theme_view('components.icon'), ['name' => 'arrow-right'])
                </span>
            </a>
        @endif
    </nav>
@endif
