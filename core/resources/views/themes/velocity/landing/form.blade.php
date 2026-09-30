@component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents])
    @include(theme_view('form'), [
        'FormSectionID' => $TopicBlockContents->module_id ?? 0,
        'viewStyle' => 'simple',
    ])
@endcomponent
