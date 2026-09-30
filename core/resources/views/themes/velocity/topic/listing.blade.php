@php
    $velocityFragmentSectionType = (int) data_get(($TopicsList ?? collect())->first(), 'webmasterSection.type', -1);
@endphp
@if(($TopicsList ?? collect())->count() > 0)
    @foreach($TopicsList as $Topic)
        @if($velocityFragmentSectionType === 0)
            @include(theme_view('components.topic-card'), [
                'topic' => $Topic,
                'variant' => 'service',
                'showMeta' => true,
                'showFields' => true,
            ])
        @else
            @include(theme_view('topic.card'), ['Topic' => $Topic])
        @endif
    @endforeach
@endif
