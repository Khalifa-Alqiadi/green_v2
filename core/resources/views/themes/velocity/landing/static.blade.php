@component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents])
    <div class="velocity-rich-content">
        {!! str_replace('"#', '"'.Request::url().'#', $TopicBlockContents->{'details_'.@Helper::currentLanguage()->code} ?? '') !!}
    </div>
@endcomponent
