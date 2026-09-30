@if(!empty($Topic))
    @include(theme_view('components.topic-card'), [
        'topic' => $Topic,
        'showFields' => true,
    ])
@endif
