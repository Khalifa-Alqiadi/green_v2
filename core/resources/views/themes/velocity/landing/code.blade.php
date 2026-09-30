@component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents, 'wide' => true])
    {!! Blade::render(
        $TopicBlockContents->{'code_'.@Helper::currentLanguage()->code} ?? ($TopicBlockContents->code ?? ''),
        ['Topic' => $Topic]
    ) !!}
@endcomponent
